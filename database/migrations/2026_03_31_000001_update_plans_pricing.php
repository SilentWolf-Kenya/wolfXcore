<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->where('name', 'LIMITED')->update([
            'price'       => 50.00,
            'description' => 'Fixed port allocations included. Great for running a single game server.',
            'memory'      => 2048,
            'cpu'         => 100,
            'disk'        => 10240,
            'databases'   => 1,
            'backups'     => 1,
            'is_featured' => false,
            'is_active'   => true,
            'updated_at'  => now(),
        ]);

        DB::table('plans')->where('name', 'UNLIMITED')->update([
            'price'       => 100.00,
            'description' => 'No port allocations — all allocation values set to zero. Full flexibility for advanced setups.',
            'memory'      => 8192,
            'cpu'         => 400,
            'disk'        => 51200,
            'databases'   => 5,
            'backups'     => 5,
            'is_featured' => true,
            'is_active'   => true,
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('plans')->where('name', 'LIMITED')->update(['price' => 150.00, 'updated_at' => now()]);
        DB::table('plans')->where('name', 'UNLIMITED')->update(['price' => 100.00, 'updated_at' => now()]);
    }
};
