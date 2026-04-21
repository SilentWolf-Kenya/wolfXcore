<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;

class WxnBotHealth extends Model
{
    protected $table = 'wxn_bot_health';
    protected $guarded = [];
    protected $casts = [
        'last_crash_at'           => 'datetime',
        'circuit_paused_until'    => 'datetime',
        'last_session_restore_at' => 'datetime',
    ];

    /**
     * Circuit-breaker constants.
     * If a bot crashes more than CRASH_THRESHOLD times within CRASH_WINDOW seconds,
     * the breaker trips and stays tripped until either the user manually starts the
     * bot from the panel or an admin clicks RESET on the Bot Health dashboard.
     * No automatic timeout — pause-until-attended is the policy.
     */
    public const CRASH_THRESHOLD = 5;
    public const CRASH_WINDOW    = 600;   // 10 min

    public function server()
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function isPaused(): bool
    {
        return $this->circuit_paused_until && $this->circuit_paused_until->isFuture();
    }

    /**
     * Record a crash and trip the breaker if threshold exceeded.
     */
    public static function recordCrash(int $serverId, string $reason = ''): self
    {
        $health = static::firstOrNew(['server_id' => $serverId]);

        // Token-bucket decay: approximates a true rolling window without
        // requiring an extra crash-events table. Each CRASH_WINDOW/CRASH_THRESHOLD
        // seconds of quiet (= 120s for the 5/600s policy) decays one crash off
        // the counter. A burst of 5 within ~10 minutes still trips the breaker;
        // a steady drip slower than 1 per 2 minutes never does.
        $current = (int) ($health->crash_count_24h ?? 0);
        if ($health->last_crash_at) {
            $elapsed   = $health->last_crash_at->diffInSeconds(now());
            $tickEvery = (int) max(1, floor(self::CRASH_WINDOW / self::CRASH_THRESHOLD));
            $decay     = (int) floor($elapsed / $tickEvery);
            $current   = max(0, $current - $decay);
        }
        $current += 1;

        $health->crash_count_24h    = $current;
        $health->last_crash_at      = now();
        $health->last_crash_reason  = substr($reason, 0, 255);

        if ($current >= self::CRASH_THRESHOLD) {
            // Sentinel far-future timestamp: breaker stays tripped until cleared by
            // user manual start (PowerController) or admin reset (SuperAdminController).
            $health->circuit_paused_until = now()->addYears(100);
        }

        $health->save();
        return $health;
    }

    public static function clearForServer(int $serverId): void
    {
        static::where('server_id', $serverId)->update([
            'crash_count_24h'      => 0,
            'circuit_paused_until' => null,
        ]);
    }
}
