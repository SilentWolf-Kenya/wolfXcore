<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wxn_bot_health', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id')->unique();
            $table->unsignedInteger('crash_count_24h')->default(0);
            $table->timestamp('last_crash_at')->nullable();
            $table->string('last_crash_reason', 255)->nullable();
            $table->timestamp('circuit_paused_until')->nullable();
            $table->unsignedInteger('session_restores')->default(0);
            $table->timestamp('last_session_restore_at')->nullable();
            $table->timestamps();

            $table->index('circuit_paused_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wxn_bot_health');
    }
};
