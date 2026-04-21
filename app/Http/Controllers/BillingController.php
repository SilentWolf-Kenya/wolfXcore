<?php

namespace Pterodactyl\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Models\Plan;
use Pterodactyl\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Pterodactyl\Services\PaystackService;
use Pterodactyl\Services\ServerProvisioningService;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\WxnNotification;

class BillingController extends Controller
{
    // Paystack Kenya only supports KES. Plan prices are stored in USD.
    const KES_RATE = 130; // 1 USD → 130 KES (update periodically)

    const DISCOUNT_RULES = [
        'STARTER'  => ['qty' => 3, 'discount_each' => 5],
        'STANDARD' => ['qty' => 3, 'discount_each' => 8],
        'PRO'      => ['qty' => 3, 'discount_each' => 12],
        'UNLIMITED' => ['qty' => 3, 'discount_each' => 15],
    ];

    public function __construct(
        protected PaystackService $paystack,
        protected ServerProvisioningService $provisioner,
    ) {
    }

    private function db(): \Illuminate\Database\Connection
    {
        return DB::connection(config('database.default'));
    }

    private function getCurrency(): string
    {
        return Setting::where('key', 'settings::payment:currency')->value('value') ?? 'KES';
    }

    /** Convert a USD amount to KES for Paystack (which only supports KES in Kenya). */
    private function toKes(float $usd): float
    {
        return round($usd * self::KES_RATE, 2);
    }

    private function requireAuth(): ?RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/auth/login');
        }
        return null;
    }

    private function calcAmount(Plan $plan, int $qty): float
    {
        $base     = (float) $plan->price;
        $rule     = self::DISCOUNT_RULES[strtoupper($plan->name)] ?? null;
        $discount = ($rule && $qty >= $rule['qty']) ? $rule['discount_each'] : 0;
        return round(($base - $discount) * $qty, 2);
    }

    private function validateEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function isAdminPlan(Plan $plan): bool
    {
        return strtoupper($plan->name) === 'ADMIN PANEL';
    }

    private function grantAdminAccess(int $userId): void
    {
        DB::table('users')->where('id', $userId)->update([
            'root_admin' => true,
            'updated_at' => now(),
        ]);
    }

    private function notifySuperAdmin(string $title, string $body): void
    {
        try {
            WxnNotification::create([
                'title'     => $title,
                'body'      => $body,
                'type'      => 'warning',
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            // Never let a notification failure affect the billing response.
        }
    }

    /**
     * Normalise any Kenyan phone number to +254XXXXXXXXX (E.164).
     */
    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (str_starts_with($digits, '254')) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+254' . substr($digits, 1);
        }
        if (strlen($digits) === 9) {
            return '+254' . $digits;
        }
        return '+' . $digits;
    }

    private function activateSubscription(int $userId, int $planId, string $reference, int $qty): int
    {
        $this->db()->table('wxn_subscriptions')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        return $this->db()->table('wxn_subscriptions')->insertGetId([
            'user_id'    => $userId,
            'plan_id'    => $planId,
            'gateway'    => 'paystack',
            'reference'  => $reference,
            'status'     => 'active',
            'starts_at'  => now(),
            'expires_at' => now()->addDays(30 * $qty),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function linkServerToSubscription(int $subscriptionId, int $serverId): void
    {
        $this->db()->table('wxn_subscriptions')
            ->where('id', $subscriptionId)
            ->update(['server_id' => $serverId, 'updated_at' => now()]);
    }

    // ──────────────────────────────────────────────────────────────────
    // GET /billing
    // ──────────────────────────────────────────────────────────────────
    public function index(): View|RedirectResponse
    {
        if ($redir = $this->requireAuth()) return $redir;

        $plans    = Plan::where('is_active', true)
                        ->whereRaw('UPPER(name) != ?', ['ADMIN PANEL'])
                        ->orderBy('price')->get();
        $currency = $this->getCurrency();
        $user     = Auth::user();

        $subscription = $this->db()->table('wxn_subscriptions')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })
            ->orderByDesc('created_at')
            ->first();

        $currentPlan = $subscription ? Plan::find($subscription->plan_id) : null;

        $payments = $this->db()->table('wxn_payments')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $paystackPublicKey  = $this->paystack->getPublicKey();
        $paystackConfigured = $this->paystack->isConfigured();
        $discountRules      = self::DISCOUNT_RULES;

        $walletBalance = (float) ($this->db()->table('wxn_wallets')
            ->where('user_id', $user->id)
            ->value('balance') ?? 0);

        return view('billing.index', compact(
            'plans', 'currency', 'subscription', 'currentPlan',
            'payments', 'paystackPublicKey', 'paystackConfigured', 'discountRules',
            'walletBalance'
        ));
    }

    // ──────────────────────────────────────────────────────────────────
    // POST /billing/initiate  (JSON)
    // ──────────────────────────────────────────────────────────────────
    public function initiate(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'plan_id'        => 'required|exists:plans,id',
            'quantity'       => 'required|integer|min:1|max:6',
            'payment_method' => 'required|in:card,mpesa,airtel',
            'phone'          => 'required_if:payment_method,mpesa,airtel|nullable|string|max:20',
            'server_name'    => 'nullable|string|max:64',
        ]);

        if (!$this->paystack->isConfigured()) {
            return response()->json(['error' => 'Payment gateway is not configured. Contact support.'], 503);
        }

        $user     = Auth::user();
        $email    = $user->email ?? '';
        $plan     = Plan::findOrFail($request->plan_id);

        if ($this->isAdminPlan($plan)) {
            return response()->json(['error' => 'This plan is not available for purchase.'], 403);
        }

        $qty       = (int) $request->quantity;
        $method    = $request->payment_method;
        $currency  = 'KES'; // Paystack Kenya only supports KES
        $amount    = $this->calcAmount($plan, $qty); // plan prices are stored in KES
        $serverName = $request->input('server_name');

        if (!$this->validateEmail($email)) {
            return response()->json([
                'error' => 'Your account email "' . $email . '" is not valid for payments. Please update it in Account settings.',
            ], 422);
        }

        $reference = 'WXN-' . strtoupper(Str::random(14));

        $this->db()->table('wxn_payments')->insert([
            'user_id'    => $user->id,
            'plan_id'    => $plan->id,
            'gateway'    => 'paystack',
            'reference'  => $reference,
            'amount'     => $amount,
            'currency'   => $currency,
            'status'     => 'pending',
            'metadata'   => json_encode([
                'plan_name'      => $plan->name,
                'user_email'     => $email,
                'quantity'       => $qty,
                'payment_method' => $method,
                'phone'          => $request->phone,
                'unit_price'     => $plan->price,
                'server_name'    => $serverName,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($method === 'card') {
            return response()->json([
                'type'        => 'card',
                'reference'   => $reference,
                'public_key'  => $this->paystack->getPublicKey(),
                'amount_kobo' => (int) round($amount * 100),
                'email'       => $email,
                'currency'    => $currency,
                'plan_name'   => strtoupper($plan->name),
            ]);
        }

        $provider = $method === 'mpesa' ? 'mpesa' : 'airtel';
        $phone    = $this->normalizePhone($request->phone ?? '');

        try {
            $result = $this->paystack->chargeMobileMoney($email, $amount, $currency, $reference, $phone, $provider);
            $status = $result['data']['status'] ?? 'unknown';

            if (in_array($status, ['pay_offline', 'pending', 'send_otp', 'success'])) {
                return response()->json([
                    'type'      => 'mobile',
                    'reference' => $reference,
                    'provider'  => strtoupper($provider),
                    'phone'     => $phone,
                    'message'   => 'STK push sent to ' . $phone . '. Approve the prompt on your phone.',
                ]);
            }

            $this->db()->table('wxn_payments')->where('reference', $reference)->update(['status' => 'failed', 'updated_at' => now()]);
            return response()->json(['error' => 'Could not initiate payment. Paystack responded: ' . $status], 422);

        } catch (\Exception $e) {
            $this->db()->table('wxn_payments')->where('reference', $reference)->update(['status' => 'failed', 'updated_at' => now()]);
            return response()->json(['error' => 'Payment initiation failed: ' . $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // POST /billing/verify  (JSON)
    // ──────────────────────────────────────────────────────────────────
    public function verify(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $request->validate(['reference' => 'required|string']);
        $reference = $request->reference;

        $payment = $this->db()->table('wxn_payments')->where('reference', $reference)->first();
        if (!$payment) {
            return response()->json(['error' => 'Payment record not found.'], 404);
        }

        if ($payment->status === 'success') {
            return response()->json(['status' => 'success', 'message' => 'Payment already confirmed.', 'redirect' => '/servers']);
        }

        try {
            $result = $this->paystack->verifyTransaction($reference);
            $data   = $result['data'];
            $status = $data['status'] ?? 'unknown';

            if ($status === 'success') {
                $this->db()->table('wxn_payments')->where('reference', $reference)->update([
                    'status'     => 'success',
                    'metadata'   => json_encode($data),
                    'updated_at' => now(),
                ]);

                $meta = json_decode($payment->metadata ?? '{}', true);
                $qty  = (int) ($meta['quantity'] ?? 1);
                $serverName = $meta['server_name'] ?? null;

                $subscriptionId = $this->activateSubscription($payment->user_id, $payment->plan_id, $reference, $qty);

                $user = Auth::user();
                $plan = Plan::find($payment->plan_id);

                if ($plan && $this->isAdminPlan($plan)) {
                    $this->grantAdminAccess($payment->user_id);
                    return response()->json([
                        'status'   => 'success',
                        'message'  => 'Admin Panel access granted! You now have full admin privileges.',
                        'redirect' => '/admin',
                    ]);
                }

                $server = $plan ? $this->provisioner->provision($user, $plan, $serverName) : null;

                if ($server) {
                    $this->linkServerToSubscription($subscriptionId, $server->id);
                } else {
                    $this->notifySuperAdmin(
                        'Payment received — server not created',
                        "User ID {$payment->user_id} paid (ref: {$reference}, plan: " . ($plan->name ?? 'unknown') . ") but no server was provisioned. Manual action may be required."
                    );
                }

                return response()->json([
                    'status'      => 'success',
                    'message'     => "Subscription activated! Your server is being provisioned.",
                    'server_uuid' => $server ? $server->uuid : null,
                    'redirect'    => '/servers',
                ]);
            }

            if (in_array($status, ['failed', 'abandoned'])) {
                $this->db()->table('wxn_payments')->where('reference', $reference)->update(['status' => 'failed', 'updated_at' => now()]);
                return response()->json(['status' => 'failed', 'message' => 'Payment failed or was cancelled.']);
            }

            return response()->json(['status' => 'pending', 'message' => 'Payment is still pending.']);

        } catch (DisplayException $e) {
            // Capacity refused — payment was successful and is recorded as
            // 'paid_unprovisioned'; surface the message to the user verbatim.
            $this->notifySuperAdmin(
                'Payment received — server not created (capacity full)',
                "User ID {$payment->user_id} paid (ref: {$reference}) but provisioning was blocked: {$e->getMessage()}"
            );
            return response()->json(['status' => 'capacity', 'message' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Verification error: ' . $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // POST /billing/wallet-pay  (JSON)
    // Pay for a plan subscription using wallet balance
    // ──────────────────────────────────────────────────────────────────
    public function walletPay(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'plan_id'     => 'required|exists:plans,id',
            'quantity'    => 'required|integer|min:1|max:6',
            'server_name' => 'nullable|string|max:64',
        ]);

        $user       = Auth::user();
        $plan       = Plan::findOrFail($request->plan_id);

        if ($this->isAdminPlan($plan)) {
            return response()->json(['error' => 'This plan is not available for purchase.'], 403);
        }

        $qty        = (int) $request->quantity;
        $amount     = $this->calcAmount($plan, $qty); // plan prices are stored in KES
        $serverName = $request->input('server_name');

        $wallet  = $this->db()->table('wxn_wallets')->where('user_id', $user->id)->first();
        $balance = (float) ($wallet->balance ?? 0);

        if ($balance < $amount) {
            return response()->json([
                'error' => "Insufficient wallet balance. You have KES " . number_format($balance, 2) . " but need KES " . number_format($amount, 2) . ".",
            ], 422);
        }

        $reference      = 'WXN-WLT-' . strtoupper(Str::random(10));
        $subscriptionId = 0;

        DB::transaction(function () use ($user, $plan, $amount, $qty, $reference, $wallet, $balance, $serverName, &$subscriptionId) {
            $newBalance = round($balance - $amount, 2);

            if ($wallet) {
                $this->db()->table('wxn_wallets')
                    ->where('user_id', $user->id)
                    ->update(['balance' => $newBalance, 'updated_at' => now()]);
            } else {
                $this->db()->table('wxn_wallets')->insert([
                    'user_id'    => $user->id,
                    'balance'    => $newBalance,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->db()->table('wxn_wallet_transactions')->insert([
                'user_id'       => $user->id,
                'type'          => 'debit',
                'amount'        => $amount,
                'balance_after' => $newBalance,
                'description'   => strtoupper($plan->name) . ' plan × ' . $qty . ' month(s)',
                'reference'     => $reference,
                'gateway'       => 'wallet',
                'status'        => 'success',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $this->db()->table('wxn_payments')->insert([
                'user_id'    => $user->id,
                'plan_id'    => $plan->id,
                'gateway'    => 'wallet',
                'reference'  => $reference,
                'amount'     => $amount,
                'currency'   => 'KES',
                'status'     => 'success',
                'metadata'   => json_encode([
                    'quantity'    => $qty,
                    'plan_name'   => $plan->name,
                    'server_name' => $serverName,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $subscriptionId = $this->activateSubscription($user->id, $plan->id, $reference, $qty);
        });

        if ($this->isAdminPlan($plan)) {
            $this->grantAdminAccess($user->id);
            return response()->json([
                'status'   => 'success',
                'message'  => 'Admin Panel access granted! You now have full admin privileges.',
                'redirect' => '/admin',
            ]);
        }

        try {
            $server = $this->provisioner->provision($user, $plan, $serverName);
        } catch (DisplayException $e) {
            $this->notifySuperAdmin(
                'Wallet payment received — server not created (capacity full)',
                "User ID {$user->id} paid via wallet (ref: {$reference}, plan: {$plan->name}) but provisioning was blocked: {$e->getMessage()}"
            );
            return response()->json(['status' => 'capacity', 'message' => $e->getMessage()], 409);
        }

        if ($server && $subscriptionId) {
            $this->linkServerToSubscription($subscriptionId, $server->id);
        } elseif (!$server) {
            $this->notifySuperAdmin(
                'Wallet payment received — server not created',
                "User ID {$user->id} paid via wallet (ref: {$reference}, plan: {$plan->name}) but no server was provisioned. Manual action may be required."
            );
        }

        return response()->json([
            'status'      => 'success',
            'message'     => "Subscription activated via wallet! Your server is being provisioned.",
            'server_uuid' => $server ? $server->uuid : null,
            'redirect'    => '/servers',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // POST /billing/wallet/deposit/initiate  (JSON)
    // Initiate a Paystack payment to top up wallet balance
    // ──────────────────────────────────────────────────────────────────
    public function initiateWalletDeposit(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'amount'         => 'required|numeric|min:40|max:100000',
            'payment_method' => 'required|in:card,mpesa,airtel',
            'phone'          => 'required_if:payment_method,mpesa,airtel|nullable|string|max:20',
        ]);

        if (!$this->paystack->isConfigured()) {
            return response()->json(['error' => 'Payment gateway is not configured. Contact support.'], 503);
        }

        $user      = Auth::user();
        $email     = $user->email ?? '';
        $amount    = round((float) $request->amount, 2); // already in KES (user inputs KES)
        $method    = $request->payment_method;
        $currency  = 'KES'; // Paystack Kenya only supports KES; deposits are in KES
        $reference = 'WXN-DEP-' . strtoupper(Str::random(12));

        if (!$this->validateEmail($email)) {
            return response()->json(['error' => 'Your account email is not valid for payments.'], 422);
        }

        $this->db()->table('wxn_wallet_transactions')->insert([
            'user_id'       => $user->id,
            'type'          => 'credit',
            'amount'        => $amount,
            'balance_after' => 0,
            'description'   => 'Wallet top-up via ' . strtoupper($method),
            'reference'     => $reference,
            'gateway'       => 'paystack',
            'status'        => 'pending',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        if ($method === 'card') {
            return response()->json([
                'type'        => 'card',
                'reference'   => $reference,
                'public_key'  => $this->paystack->getPublicKey(),
                'amount_kobo' => (int) round($amount * 100),
                'email'       => $email,
                'currency'    => $currency,
                'label'       => 'Wallet Top-Up',
            ]);
        }

        $provider = $method === 'mpesa' ? 'mpesa' : 'airtel';
        $phone    = $this->normalizePhone($request->phone ?? '');

        try {
            $result = $this->paystack->chargeMobileMoney($email, $amount, $currency, $reference, $phone, $provider);
            $status = $result['data']['status'] ?? 'unknown';

            if (in_array($status, ['pay_offline', 'pending', 'send_otp', 'success'])) {
                return response()->json([
                    'type'      => 'mobile',
                    'reference' => $reference,
                    'provider'  => strtoupper($provider),
                    'phone'     => $phone,
                    'message'   => 'STK push sent to ' . $phone . '. Approve the prompt on your phone.',
                ]);
            }

            $this->db()->table('wxn_wallet_transactions')
                ->where('reference', $reference)
                ->update(['status' => 'failed', 'updated_at' => now()]);

            return response()->json(['error' => 'Could not initiate deposit. Paystack responded: ' . $status], 422);

        } catch (\Exception $e) {
            $this->db()->table('wxn_wallet_transactions')
                ->where('reference', $reference)
                ->update(['status' => 'failed', 'updated_at' => now()]);
            return response()->json(['error' => 'Deposit initiation failed: ' . $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // POST /billing/wallet/deposit/verify  (JSON)
    // Verify a wallet deposit and credit the balance
    // ──────────────────────────────────────────────────────────────────
    public function verifyWalletDeposit(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $request->validate(['reference' => 'required|string']);
        $reference = $request->reference;

        $txn = $this->db()->table('wxn_wallet_transactions')->where('reference', $reference)->first();
        if (!$txn) {
            return response()->json(['error' => 'Transaction not found.'], 404);
        }

        if ($txn->status === 'success') {
            return response()->json(['status' => 'success', 'message' => 'Deposit already confirmed.']);
        }

        try {
            $result = $this->paystack->verifyTransaction($reference);
            $data   = $result['data'];
            $status = $data['status'] ?? 'unknown';

            if ($status === 'success') {
                $amount = (float) $txn->amount;
                $userId = $txn->user_id;

                $newBalance = DB::transaction(function () use ($userId, $amount, $reference) {
                    $wallet  = $this->db()->table('wxn_wallets')->where('user_id', $userId)->first();
                    $current = (float) ($wallet->balance ?? 0);
                    $newBal  = round($current + $amount, 2);

                    if ($wallet) {
                        $this->db()->table('wxn_wallets')
                            ->where('user_id', $userId)
                            ->update(['balance' => $newBal, 'updated_at' => now()]);
                    } else {
                        $this->db()->table('wxn_wallets')->insert([
                            'user_id'    => $userId,
                            'balance'    => $newBal,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $this->db()->table('wxn_wallet_transactions')
                        ->where('reference', $reference)
                        ->update([
                            'status'        => 'success',
                            'balance_after' => $newBal,
                            'updated_at'    => now(),
                        ]);

                    return $newBal;
                });

                return response()->json([
                    'status'      => 'success',
                    'message'     => 'KES ' . number_format($amount, 2) . ' deposited to your wallet.',
                    'new_balance' => $newBalance,
                ]);
            }

            if (in_array($status, ['failed', 'abandoned'])) {
                $this->db()->table('wxn_wallet_transactions')
                    ->where('reference', $reference)
                    ->update(['status' => 'failed', 'updated_at' => now()]);
                return response()->json(['status' => 'failed', 'message' => 'Deposit failed or was cancelled.']);
            }

            return response()->json(['status' => 'pending', 'message' => 'Payment is still pending.']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Verification error: ' . $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // GET /billing/callback  — Paystack redirect fallback
    // ──────────────────────────────────────────────────────────────────
    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->query('reference') ?? $request->query('trxref');
        if (!$reference) {
            return redirect('/billing')->with('error', 'Invalid payment reference.');
        }

        $payment = $this->db()->table('wxn_payments')->where('reference', $reference)->first();
        if (!$payment) {
            return redirect('/billing')->with('error', 'Payment record not found.');
        }

        if ($payment->status === 'success') {
            return redirect('/servers')->with('success', 'Payment already confirmed. Your server is ready!');
        }

        try {
            $result = $this->paystack->verifyTransaction($reference);
            $data   = $result['data'];

            if ($data['status'] === 'success') {
                $this->db()->table('wxn_payments')->where('reference', $reference)->update([
                    'status'     => 'success',
                    'metadata'   => json_encode($data),
                    'updated_at' => now(),
                ]);

                $meta = json_decode($payment->metadata ?? '{}', true);
                $qty  = (int) ($meta['quantity'] ?? 1);
                $serverName = $meta['server_name'] ?? null;

                $subscriptionId = $this->activateSubscription($payment->user_id, $payment->plan_id, $reference, $qty);

                $user = Auth::user();
                $plan = Plan::find($payment->plan_id);

                if ($plan && $this->isAdminPlan($plan)) {
                    $this->grantAdminAccess($payment->user_id);
                    return redirect('/admin')->with('success', "Admin Panel access granted! You now have full admin privileges.");
                }

                if ($plan && $user) {
                    $server = $this->provisioner->provision($user, $plan, $serverName);
                    if ($server) {
                        $this->linkServerToSubscription($subscriptionId, $server->id);
                    } else {
                        $this->notifySuperAdmin(
                            'Payment received — server not created',
                            "User ID {$payment->user_id} paid via Paystack (ref: {$reference}, plan: " . ($plan->name ?? 'unknown') . ") but no server was provisioned. Manual action may be required."
                        );
                    }
                }

                return redirect('/servers')->with('success', "Payment confirmed! Your {$qty}-month subscription is active and your server is being provisioned.");
            }

            $this->db()->table('wxn_payments')->where('reference', $reference)->update(['status' => 'failed', 'updated_at' => now()]);
            return redirect('/billing')->with('error', 'Payment was not successful. Please try again.');

        } catch (DisplayException $e) {
            $this->notifySuperAdmin(
                'Payment received — server not created (capacity full)',
                "User paid via Paystack (ref: {$reference}) but provisioning was blocked: {$e->getMessage()}"
            );
            return redirect('/billing')->with('error', $e->getMessage() . ' Your payment is recorded (ref: ' . $reference . ') and will be provisioned once capacity frees up — contact support.');
        } catch (\Exception $e) {
            return redirect('/billing')->with('error', 'Could not verify payment. Contact support with ref: ' . $reference);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // (Pesapal removed — all payments use Paystack)
    // ──────────────────────────────────────────────────────────────────
}
