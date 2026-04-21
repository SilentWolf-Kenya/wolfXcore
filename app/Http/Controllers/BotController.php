<?php

namespace Pterodactyl\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\BotRepo;
use Pterodactyl\Models\BotAllocation;
use Pterodactyl\Models\Plan;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\BotRepoService;
use Pterodactyl\Services\ServerProvisioningService;
use Pterodactyl\Services\PaystackService;
use Pterodactyl\Exceptions\DisplayException;

class BotController extends Controller
{
    public function __construct(
        protected BotRepoService $botService,
        protected ServerProvisioningService $provisioner,
        protected PaystackService $paystack,
    ) {}

    private function requireAuth(): ?RedirectResponse
    {
        if (!Auth::check()) return redirect('/auth/login');
        return null;
    }

    /**
     * GET /bots  — Bot marketplace listing
     */
    public function index(): View|RedirectResponse
    {
        if ($r = $this->requireAuth()) return $r;

        $repos = BotRepo::where('is_active', true)
            ->withCount(['allocations as available_count' => fn($q) => $q->where('status', 'available')])
            ->orderBy('name')
            ->get();

        // Auto-heal: if env_schema is empty, re-parse from stored app_json_raw or re-fetch from GitHub
        foreach ($repos as $repo) {
            if (empty($repo->env_schema) || $repo->env_schema === '[]' || $repo->env_schema === 'null') {
                try {
                    $repoUrl = $repo->repo_url ?? $repo->git_address ?? '';
                    $raw = $repo->app_json_raw ? json_decode($repo->app_json_raw, true) : null;
                    if (!$raw && $repoUrl) {
                        $raw = $this->botService->fetchAppJson($repoUrl);
                        $repo->app_json_raw = json_encode($raw);
                    }
                    if ($raw) {
                        $parsed = $this->botService->parseAppJson($raw, $repoUrl);
                        $repo->env_schema   = $parsed['env_schema'];
                        $repo->image_url    = $repo->image_url    ?: ($parsed['image_url'] ?? null);
                        $repo->description  = $repo->description  ?: ($parsed['description'] ?? null);
                        $repo->save();
                    }
                } catch (\Exception $e) {
                    Log::warning("env_schema auto-heal failed for repo {$repo->id}: " . $e->getMessage());
                }
            }
        }

        $plans = Plan::where('is_active', true)
            ->whereNotIn('name', ['ADMIN PANEL', 'Admin Panel'])
            ->orderBy('price')
            ->get();

        $currency            = 'KES'; // wallet and Paystack always operate in KES
        $paystackPublicKey   = $this->paystack->getPublicKey();
        $paystackConfigured  = $this->paystack->isConfigured();
        $isSuperAdmin        = session('wxn_super', false);

        $walletBalance = (float) (DB::table('wxn_wallets')
            ->where('user_id', Auth::id())
            ->value('balance') ?? 0);

        return view('bots.index', compact(
            'repos', 'plans', 'currency',
            'paystackPublicKey', 'paystackConfigured', 'walletBalance', 'isSuperAdmin'
        ));
    }

    /**
     * GET /bots/configure/{uuid}  — Config page for user's assigned bot server
     */
    public function configure(string $uuid): View|RedirectResponse
    {
        if ($r = $this->requireAuth()) return $r;

        $botConfig = DB::table('wxn_bot_configs')
            ->where('server_uuid', $uuid)
            ->where('user_id', Auth::id())
            ->first();

        if (!$botConfig) {
            return redirect('/servers')->with('error', 'Bot server not found.');
        }

        $repo      = BotRepo::find($botConfig->repo_id);
        $server    = Server::where('uuid', $uuid)->first();
        $schema    = json_decode($repo->env_schema ?? '[]', true) ?? [];
        $saved     = json_decode($botConfig->configs ?? '{}', true) ?? [];

        return view('bots.configure', compact('repo', 'server', 'schema', 'saved', 'botConfig', 'uuid'));
    }

    /**
     * POST /bots/configure/{uuid}  — Save configs and start bot
     */
    public function saveConfig(Request $request, string $uuid): JsonResponse
    {
        if (!Auth::check()) return response()->json(['error' => 'Unauthenticated.'], 401);

        $botConfig = DB::table('wxn_bot_configs')
            ->where('server_uuid', $uuid)
            ->where('user_id', Auth::id())
            ->first();

        if (!$botConfig) return response()->json(['error' => 'Bot server not found.'], 404);

        $repo   = BotRepo::find($botConfig->repo_id);
        $schema = json_decode($repo->env_schema ?? '[]', true) ?? [];

        $configs = [];
        foreach ($schema as $field) {
            $val = $request->input($field['key'], $field['default'] ?? '');
            if ($field['required'] && empty($val)) {
                return response()->json(['error' => "Field \"{$field['key']}\" is required."], 422);
            }
            $configs[$field['key']] = $val;
        }

        DB::table('wxn_bot_configs')
            ->where('server_uuid', $uuid)
            ->where('user_id', Auth::id())
            ->update([
                'configs'    => json_encode($configs),
                'status'     => 'configured',
                'updated_at' => now(),
            ]);

        try {
            $this->applyConfigsToServer($uuid, $repo, $configs);
        } catch (\Exception $e) {
            Log::error('BotConfig apply failed: ' . $e->getMessage());
            return response()->json(['error' => 'Configs saved but failed to apply to server: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'status'   => 'success',
            'message'  => 'Bot configured! Launching server console...',
            'redirect' => '/server/' . $uuid,
        ]);
    }

    /**
     * POST /bots/initiate  — Start a bot purchase (Paystack)
     */
    public function initiate(Request $request): JsonResponse
    {
        if (!Auth::check()) return response()->json(['error' => 'Unauthenticated.'], 401);

        $request->validate([
            'repo_id'        => 'required|exists:wxn_bot_repos,id',
            'plan_id'        => 'required|exists:plans,id',
            'payment_method' => session('wxn_super') ? 'nullable|string' : 'required|in:card,mpesa,airtel,wallet',
            'phone'          => 'required_if:payment_method,mpesa,airtel|nullable|string',
            'configs'        => 'nullable|array',
        ]);

        $user    = Auth::user();
        $repo    = BotRepo::findOrFail($request->repo_id);
        $plan    = Plan::findOrFail($request->plan_id);
        $amount  = (float) $plan->price;
        $method  = $request->payment_method;
        $configs = $request->input('configs', []);

        // Super admins deploy for free — skip payment entirely
        if (session('wxn_super')) {
            $ref  = 'WXN-BOT-ADMIN-' . strtoupper(\Illuminate\Support\Str::random(8));
            try {
                $uuid = $this->completeBotPurchase($user, $repo, $plan, $ref, $configs);
            } catch (DisplayException $e) {
                return response()->json(['error' => $e->getMessage()], 503);
            }
            return response()->json([
                'status'   => 'success',
                'message'  => 'Bot deployed for free (Super Admin).',
                'redirect' => $uuid ? '/server/' . $uuid : '/servers',
            ]);
        }

        if ($method === 'wallet') {
            $balance = (float) (DB::table('wxn_wallets')->where('user_id', $user->id)->value('balance') ?? 0);
            if ($balance < $amount) {
                return response()->json(['error' => "Insufficient wallet balance. You have KES {$balance} but need KES {$amount}."], 422);
            }
            $ref = 'WXN-BOT-WLT-' . strtoupper(\Illuminate\Support\Str::random(10));
            try {
                $uuid = $this->completeBotPurchase($user, $repo, $plan, $ref, $configs);
            } catch (DisplayException $e) {
                // refund: don't deduct wallet, surface the message verbatim
                return response()->json(['error' => $e->getMessage()], 503);
            }
            $newBal = round($balance - $amount, 2);
            DB::table('wxn_wallets')->where('user_id', $user->id)->update(['balance' => $newBal, 'updated_at' => now()]);
            DB::table('wxn_wallet_transactions')->insert([
                'user_id' => $user->id, 'type' => 'debit', 'amount' => $amount,
                'balance_after' => $newBal, 'description' => "Bot: {$repo->name}",
                'reference' => $ref, 'gateway' => 'wallet', 'status' => 'success',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return response()->json([
                'status'   => 'success',
                'message'  => "Bot deployed! Loading your server console...",
                'redirect' => $uuid ? '/server/' . $uuid : '/servers',
            ]);
        }

        if (!$this->paystack->isConfigured()) {
            return response()->json(['error' => 'Payment gateway not configured.'], 503);
        }

        $currency  = 'KES'; // Paystack Kenya only supports KES
        $reference = 'WXN-BOT-' . strtoupper(\Illuminate\Support\Str::random(12));

        DB::table('wxn_payments')->insert([
            'user_id'    => $user->id,
            'plan_id'    => $plan->id,
            'gateway'    => 'paystack',
            'reference'  => $reference,
            'amount'     => $amount,
            'currency'   => $currency,
            'status'     => 'pending',
            'metadata'   => json_encode([
                'type'           => 'bot',
                'repo_id'        => $repo->id,
                'repo_name'      => $repo->name,
                'plan_name'      => $plan->name,
                'payment_method' => $method,
                'phone'          => $request->phone,
                'configs'        => $configs,
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
                'email'       => $user->email,
                'currency'    => $currency,
                'plan_name'   => $repo->name,
            ]);
        }

        $phone    = $this->normalizePhone($request->phone ?? '');
        $provider = $method === 'mpesa' ? 'mpesa' : 'airtel';

        try {
            $result = $this->paystack->chargeMobileMoney($user->email, $amount, $currency, $reference, $phone, $provider);
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

            return response()->json(['error' => 'Could not initiate mobile payment: ' . ($result['message'] ?? 'Unknown error.')], 422);

        } catch (\Exception $e) {
            Log::error('Mobile money charge failed: ' . $e->getMessage());
            return response()->json(['error' => 'Mobile payment failed: ' . $e->getMessage()], 422);
        }
    }

    /**
     * POST /bots/verify  — Verify a bot payment and assign slot
     */
    public function verify(Request $request): JsonResponse
    {
        if (!Auth::check()) return response()->json(['error' => 'Unauthenticated.'], 401);

        $request->validate(['reference' => 'required|string']);
        $reference = $request->reference;

        $payment = DB::table('wxn_payments')->where('reference', $reference)->first();
        if (!$payment) return response()->json(['error' => 'Payment not found.'], 404);

        if ($payment->status === 'success') {
            $alloc = BotAllocation::where('user_id', $payment->user_id)->where('status', 'assigned')->latest()->first();
            return response()->json([
                'status'   => 'success',
                'message'  => 'Already confirmed.',
                'redirect' => $alloc ? '/server/' . $alloc->server_uuid : '/servers',
            ]);
        }

        try {
            $result = $this->paystack->verifyTransaction($reference);
            $data   = $result['data'];
            $status = $data['status'] ?? 'unknown';

            if ($status === 'success') {
                $meta = json_decode($payment->metadata ?? '{}', true);
                DB::table('wxn_payments')->where('reference', $reference)->update([
                    'status'     => 'success',
                    'metadata'   => json_encode(array_merge($meta, ['verified_data' => $data])),
                    'updated_at' => now(),
                ]);

                $repo    = BotRepo::find($meta['repo_id'] ?? 0);
                $plan    = Plan::find($payment->plan_id);
                $user    = \Pterodactyl\Models\User::find($payment->user_id);
                $configs = $meta['configs'] ?? [];

                $uuid = null;
                $capacityMessage = null;
                if ($repo && $plan && $user) {
                    try {
                        $uuid = $this->completeBotPurchase($user, $repo, $plan, $reference, $configs);
                    } catch (DisplayException $e) {
                        // payment already taken; flag for manual reconciliation but tell the user clearly
                        $capacityMessage = $e->getMessage();
                        Log::error("WolfXcore: payment {$reference} succeeded but provisioning refused: {$capacityMessage}");
                        DB::table('wxn_payments')->where('reference', $reference)->update([
                            'status'     => 'paid_unprovisioned',
                            'updated_at' => now(),
                        ]);
                    }
                }

                if ($capacityMessage) {
                    return response()->json([
                        'status'   => 'capacity_refused',
                        'message'  => $capacityMessage . ' Your payment is safe — support will contact you within 24 hours.',
                        'redirect' => '/servers',
                    ], 503);
                }

                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Payment confirmed! Launching your bot...',
                    'redirect' => $uuid ? '/server/' . $uuid : '/servers',
                ]);
            }

            if (in_array($status, ['failed', 'abandoned'])) {
                DB::table('wxn_payments')->where('reference', $reference)->update(['status' => 'failed', 'updated_at' => now()]);
                return response()->json(['status' => 'failed', 'message' => 'Payment failed or cancelled.']);
            }

            return response()->json(['status' => 'pending', 'message' => 'Payment still pending.']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Assign a pre-allocated slot (or provision on-demand), apply configs, and create bot_config record.
     * Returns the server UUID or null.
     */
    private function completeBotPurchase($user, BotRepo $repo, Plan $plan, string $reference, array $configs = []): ?string
    {
        $slot = BotAllocation::where('repo_id', $repo->id)->where('status', 'available')->lockForUpdate()->first();

        $serverUuid = null;

        if ($slot) {
            $slot->update(['status' => 'assigned', 'user_id' => $user->id, 'assigned_at' => now()]);
            $server = Server::find($slot->server_id);
            if ($server) {
                $server->update(['owner_id' => $user->id]);
                $serverUuid = $server->uuid;
            }
        } else {
            $server = $this->provisioner->provisionBotSlot($user, $plan, $repo);
            if ($server) {
                BotAllocation::create([
                    'repo_id'     => $repo->id,
                    'server_id'   => $server->id,
                    'server_uuid' => $server->uuid,
                    'status'      => 'assigned',
                    'user_id'     => $user->id,
                    'assigned_at' => now(),
                ]);
                $serverUuid = $server->uuid;
            }
        }

        if ($serverUuid) {
            DB::table('wxn_bot_configs')->updateOrInsert(
                ['user_id' => $user->id, 'server_uuid' => $serverUuid],
                [
                    'repo_id'    => $repo->id,
                    'configs'    => json_encode($configs),
                    'status'     => empty($configs) ? 'pending' : 'configured',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            if (!empty($configs)) {
                try {
                    $this->applyConfigsToServer($serverUuid, $repo, $configs);
                } catch (\Exception $e) {
                    Log::warning('Post-payment config apply failed: ' . $e->getMessage());
                }
            }
        }

        DB::table('wxn_subscriptions')->where('user_id', $user->id)->where('status', 'active')->update(['status' => 'cancelled', 'updated_at' => now()]);
        DB::table('wxn_subscriptions')->insert([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'gateway' => 'paystack',
            'reference' => $reference, 'status' => 'active',
            'starts_at' => now(), 'expires_at' => now()->addDays(30),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $serverUuid;
    }

    /**
     * Apply env configs to the wolfXcore server via Wings startup variables.
     */
    private function applyConfigsToServer(string $uuid, BotRepo $repo, array $configs): void
    {
        $server = Server::where('uuid', $uuid)->firstOrFail();
        $egg    = \Pterodactyl\Models\Egg::with('variables')->find($server->egg_id);

        foreach ($configs as $key => $value) {
            $variable = $egg?->variables->firstWhere('env_variable', $key);
            if ($variable) {
                \Pterodactyl\Models\ServerVariable::updateOrCreate(
                    ['server_id' => $server->id, 'variable_id' => $variable->id],
                    ['variable_value' => $value]
                );
            }
        }

        $gitVar = $egg?->variables->firstWhere('env_variable', 'GIT_ADDRESS');
        if ($gitVar) {
            \Pterodactyl\Models\ServerVariable::updateOrCreate(
                ['server_id' => $server->id, 'variable_id' => $gitVar->id],
                ['variable_value' => $repo->git_address]
            );
        }

        $mainVar = $egg?->variables->firstWhere('env_variable', 'MAIN_FILE');
        if ($mainVar) {
            \Pterodactyl\Models\ServerVariable::updateOrCreate(
                ['server_id' => $server->id, 'variable_id' => $mainVar->id],
                ['variable_value' => $repo->main_file]
            );
        }

        DB::table('wxn_bot_configs')->where('server_uuid', $uuid)->update(['status' => 'starting', 'updated_at' => now()]);
    }

    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (str_starts_with($digits, '254')) return '+' . $digits;
        if (str_starts_with($digits, '0') && strlen($digits) === 10) return '+254' . substr($digits, 1);
        if (strlen($digits) === 9) return '+254' . $digits;
        return '+' . $digits;
    }
}
