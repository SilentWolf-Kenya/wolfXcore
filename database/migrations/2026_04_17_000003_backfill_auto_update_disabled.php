<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Backfill: disable AUTO_UPDATE on every existing bot. This stops the
        // npm-install-on-restart behavior that's been a major cause of crashes
        // on this host. Owners can opt back in per server from the panel UI.
        DB::table('server_variables')
            ->whereIn('variable_id', function ($q) {
                $q->select('id')->from('egg_variables')->where('env_variable', 'AUTO_UPDATE');
            })
            ->where('variable_value', '!=', '0')
            ->update(['variable_value' => '0', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // No-op: restoring AUTO_UPDATE=1 on rollback would re-introduce the
        // crash pattern this migration was created to fix.
    }
};
