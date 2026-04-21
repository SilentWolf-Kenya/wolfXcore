<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('plans')
            ->whereRaw('UPPER(name) = ?', ['ADMIN PANEL'])
            ->exists();

        if (!$exists) {
            DB::table('plans')->insert([
                'name'        => 'Admin Panel',
                'description' => 'Grants you full admin access to the wolfXcore panel. Create and manage servers, users, nodes, and eggs directly from the admin area.',
                'price'       => 300.00,
                'memory'      => 0,
                'cpu'         => 0,
                'disk'        => 0,
                'io'          => 500,
                'databases'   => 0,
                'backups'     => 0,
                'is_featured' => false,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('plans')->whereRaw('UPPER(name) = ?', ['ADMIN PANEL'])->delete();
    }
};
