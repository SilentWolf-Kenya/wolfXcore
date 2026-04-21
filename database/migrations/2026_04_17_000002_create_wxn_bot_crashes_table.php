<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Append-only event log of bot-health events (crashes from Wings AND session
        // restores from the cron/pre-start hook). Used for accurate 24h/7d telemetry on
        // the Bot Health dashboard. Separate from wxn_bot_health.crash_count_24h which
        // is a token-bucket approximation tuned for the breaker, not for reporting.
        // The table name is `wxn_bot_crashes` for historical reasons; treat it as
        // "bot health events" for any new event types.
        Schema::create('wxn_bot_crashes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id');
            // Known event values:
            //   server:power.crashed       — Wings detected unexpected exit
            //   server:power.oom_killed    — Wings detected OOM kill
            //   server:installer.crashed   — Wings install script failed
            //   panel:session.restored     — restore script repaired a session
            $table->string('event', 64);
            $table->string('reason', 255)->nullable();
            $table->timestamp('occurred_at');
            $table->index(['server_id', 'occurred_at']);
            $table->index('occurred_at');
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wxn_bot_crashes');
    }
};
