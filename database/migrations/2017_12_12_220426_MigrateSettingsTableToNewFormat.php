<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateSettingsTableToNewFormat extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')->truncate();

        // SQLite does not support adding a primary key column via ALTER TABLE.
        // Recreate the table with the id column included.
        Schema::drop('settings');
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key')->unique();
            $table->text('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('settings');
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->unique();
            $table->text('value');
        });
    }
}
