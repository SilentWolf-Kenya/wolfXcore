<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wxn_subscriptions', function (Blueprint $table) {
            // servers.id is unsignedInteger (Laravel increments()), so we match that type.
            $table->unsignedInteger('server_id')->nullable()->after('user_id');
            $table->index('server_id', 'wxn_subs_server_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('wxn_subscriptions', function (Blueprint $table) {
            $table->dropIndex('wxn_subs_server_id_idx');
            $table->dropColumn('server_id');
        });
    }
};
