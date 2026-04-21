<?php

namespace Pterodactyl\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Pterodactyl\Models\Setting;
use Pterodactyl\Services\PaystackService;

class WalletController extends Controller
{
    public function __construct(protected PaystackService $paystack)
    {
    }

    private function getCurrency(): string
    {
        return Setting::where('key', 'settings::payment:currency')->value('value') ?? 'KES';
    }

    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (str_starts_with($digits, '254')) return '+' . $digits;
        if (str_starts_with($digits, '0') && strlen($digits) === 10) return '+254' . substr($digits, 1);
        if (strlen($digits) === 9) return '+254' . $digits;
        return '+' . $digits;
    }

    private function getOrCreateWallet(int $userId): object
    {
        $wallet = DB::table('wxn_wallets')->where('user_id', $userId)->first();
        if (!$wallet) {
            DB::table('wxn_wallets')->insert([
                'user_id'    => $userId,
                'balance'    => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $wallet = DB::table('wxn_wallets')->where('user_id', $userId)->first();
        }
        return $wallet;
    }

    // GET /api/client/wallet
    public function balance(): JsonResponse
    {
        $user   = Auth::user();
        $wallet = $this->getOrCreateWallet($user->id);

        $stats = DB::table('wxn_wallet_transactions')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw("
                SUM(CASE WHEN type='credit' THEN amount ELSE 0 END) as total_credited,
                SUM(CASE WHEN type='debit'  THEN amount ELSE 0 END) as total_debited
            ")
            ->first();

        return response()->json([
            'balance'        => (float) $wallet->balance,
            'total_credited' => (float) ($stats->total_credited ?? 0),
            'total_debited'  => (float) ($stats->total_debited  ?? 0),
            'currency'       => 'KES', // wallet always operates in KES
        ]);
    }

    // GET /api/client/wallet/transactions
    public function transactions(): JsonResponse
    {
        $user = Auth::user();

        $rows = DB::table('wxn_wallet_transactions')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'type', 'amount', 'balance_after', 'description', 'reference', 'gateway', 'status', 'created_at']);

        return response()->json(['data' => $rows]);
    }

    // POST /api/client/wallet/topup
    public function topup(Request $request): JsonResponse
    {
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
        $amount    = (float) $request->amount; // user inputs KES amount directly
        $method    = $request->payment_method;
        $currency  = 'KES'; // Paystack Kenya only supports KES
        $reference = 'WXN-WAL-' . strtoupper(Str::random(12));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Your account email is not valid for payments.'], 422);
        }

        $this->getOrCreateWallet($user->id);

        DB::table('wxn_wallet_transactions')->insert([
            'user_id'       => $user->id,
            'type'          => 'credit',
            'amount'        => $amount,
            'balance_after' => 0,
            'description'   => 'Wallet top-up via ' . strtoupper($method),
            'reference'     => $reference,
            'gateway'       => 'paystack',
            'status'        => 'pending',
            'metadata'      => json_encode([
                'payment_method' => $method,
                'user_email'     => $email,
                'phone'          => $request->phone,
            ]),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        if ($method === 'card') {
            return response()->json([
                'type'        => 'card',
                'reference'   => $reference,
                'public_key'  => $this->paystack->getPublicKey(),
                'amount_kobo' => (int) round($amount * 100),
                'email'       => $email,
                'currency'    => $currency,
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

            DB::table('wxn_wallet_transactions')->where('reference', $reference)->update(['status' => 'failed', 'updated_at' => now()]);
            return response()->json(['error' => 'Could not initiate payment. Status: ' . $status], 422);

        } catch (\Exception $e) {
            DB::table('wxn_wallet_transactions')->where('reference', $reference)->update(['status' => 'failed', 'updated_at' => now()]);
            return response()->json(['error' => 'Payment initiation failed: ' . $e->getMessage()], 500);
        }
    }

    // POST /api/client/wallet/verify
    public function verify(Request $request): JsonResponse
    {
        $request->validate(['reference' => 'required|string']);

        $user = Auth::user();
        $ref  = $request->reference;
        $txn  = DB::table('wxn_wallet_transactions')->where('reference', $ref)->where('user_id', $user->id)->first();

        if (!$txn) {
            return response()->json(['error' => 'Transaction not found.'], 404);
        }

        if ($txn->status === 'completed') {
            return response()->json(['status' => 'success', 'message' => 'Top-up already confirmed.']);
        }

        try {
            $result = $this->paystack->verifyTransaction($ref);
            $data   = $result['data'];
            $status = $data['status'] ?? 'unknown';

            if ($status === 'success') {
                $wallet      = $this->getOrCreateWallet($user->id);
                $newBalance  = round((float) $wallet->balance + (float) $txn->amount, 2);

                DB::table('wxn_wallets')
                    ->where('user_id', $user->id)
                    ->update(['balance' => $newBalance, 'updated_at' => now()]);

                DB::table('wxn_wallet_transactions')
                    ->where('reference', $ref)
                    ->update([
                        'status'      => 'completed',
                        'balance_after' => $newBalance,
                        'metadata'    => json_encode($data),
                        'updated_at'  => now(),
                    ]);

                return response()->json([
                    'status'      => 'success',
                    'message'     => 'Wallet topped up with KES ' . number_format($txn->amount, 2) . '!',
                    'new_balance' => $newBalance,
                ]);
            }

            if (in_array($status, ['failed', 'abandoned'])) {
                DB::table('wxn_wallet_transactions')->where('reference', $ref)->update(['status' => 'failed', 'updated_at' => now()]);
                return response()->json(['status' => 'failed', 'message' => 'Payment failed or was cancelled.']);
            }

            return response()->json(['status' => 'pending', 'message' => 'Payment is still pending.']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Verification error: ' . $e->getMessage()], 500);
        }
    }
}
