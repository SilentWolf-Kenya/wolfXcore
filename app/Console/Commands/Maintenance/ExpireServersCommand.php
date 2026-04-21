<?php

namespace Pterodactyl\Console\Commands\Maintenance;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Services\Servers\ServerDeletionService;

class ExpireServersCommand extends Command
{
    protected $signature   = 'wxn:expire-servers {--dry-run : List affected servers without taking action}';
    protected $description = 'Suspend servers whose subscription has expired, and delete servers that have been suspended past the grace period.';

    private const GRACE_HOURS = 24;

    public function __construct(
        protected SuspensionService $suspension,
        protected ServerDeletionService $deletion,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now    = Carbon::now();

        // ── 1. Suspend servers whose active subscription has expired ─────────
        // Only servers with a direct server_id link are touched automatically.
        // Servers with no billing record are intentionally skipped.
        $expiredSubs = DB::table('wxn_subscriptions')
            ->where('status', 'active')
            ->whereNotNull('server_id')
            ->where('expires_at', '<', $now)
            ->get();

        foreach ($expiredSubs as $sub) {
            $server = Server::find($sub->server_id);

            // Orphaned subscription — server already gone; just clean up.
            if (!$server) {
                if (!$dryRun) {
                    DB::table('wxn_subscriptions')->where('id', $sub->id)->update([
                        'status'     => 'expired',
                        'updated_at' => now(),
                    ]);
                }
                $this->line("[cleanup] Subscription #{$sub->id} — server #{$sub->server_id} not found, marked expired");
                continue;
            }

            $this->line("[expire] Server #{$server->id} ({$server->name}) — subscription expired at {$sub->expires_at}");

            if ($dryRun) {
                continue;
            }

            // Only suspend if not already suspended.
            if (!$server->isSuspended()) {
                try {
                    $this->suspension->toggle($server, SuspensionService::ACTION_SUSPEND);
                    // Mark subscription expired ONLY after suspension succeeds.
                    DB::table('wxn_subscriptions')->where('id', $sub->id)->update([
                        'status'     => 'expired',
                        'updated_at' => now(),
                    ]);
                    $this->info("  → Suspended & marked expired: server #{$server->id}");
                } catch (\Exception $e) {
                    // Suspension failed — leave subscription as 'active' so it
                    // will be retried on the next daily run.
                    $this->warn("  ! Could not suspend server #{$server->id}: {$e->getMessage()} (will retry tomorrow)");
                }
            } else {
                // Already suspended by another means; still mark expired.
                DB::table('wxn_subscriptions')->where('id', $sub->id)->update([
                    'status'     => 'expired',
                    'updated_at' => now(),
                ]);
                $this->line("  → Already suspended; marked expired: server #{$server->id}");
            }
        }

        // ── 2. Delete servers past the grace period ──────────────────────────
        // Conditions: subscription status = 'expired', updated_at > GRACE_HOURS ago,
        // AND the server must actually be in a suspended state (safety gate).
        $graceDeadline = $now->copy()->subHours(self::GRACE_HOURS);

        $pastGraceSubs = DB::table('wxn_subscriptions')
            ->where('status', 'expired')
            ->whereNotNull('server_id')
            ->where('updated_at', '<', $graceDeadline)
            ->get();

        foreach ($pastGraceSubs as $sub) {
            $server = Server::find($sub->server_id);
            if (!$server) {
                continue;
            }

            // Safety gate: only delete servers that are confirmed suspended.
            if (!$server->isSuspended()) {
                $this->warn("[skip-delete] Server #{$server->id} ({$server->name}) — not suspended, skipping deletion");
                continue;
            }

            $this->line("[delete] Server #{$server->id} ({$server->name}) — suspended, grace period elapsed at {$graceDeadline}");

            if ($dryRun) {
                continue;
            }

            try {
                $this->deletion->withForce(false)->handle($server);
                DB::table('wxn_subscriptions')->where('id', $sub->id)->update([
                    'server_id'  => null,
                    'updated_at' => now(),
                ]);
                $this->info("  → Deleted server #{$server->id}");
            } catch (\Exception $e) {
                $this->warn("  ! Could not delete server #{$server->id}: {$e->getMessage()}");
            }
        }

        if ($dryRun) {
            $this->warn('Dry-run mode — no changes were made.');
        }

        $this->info('wxn:expire-servers complete.');
        return 0;
    }
}
