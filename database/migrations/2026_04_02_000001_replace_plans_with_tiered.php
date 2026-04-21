<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->delete();

        DB::table('plans')->insert([
            [
                'name'        => 'starter',
                'description' => 'Perfect entry-level plan. Spin up lightweight game servers, bots, or small projects.',
                'price'       => 40.00,
                'memory'      => 2048,
                'cpu'         => 100,
                'disk'        => 10240,
                'io'          => 500,
                'databases'   => 1,
                'backups'     => 1,
                'is_featured' => false,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'standard',
                'description' => 'Most popular. Runs demanding game servers and multi-service bots with ease.',
                'price'       => 80.00,
                'memory'      => 5120,
                'cpu'         => 150,
                'disk'        => 20480,
                'io'          => 500,
                'databases'   => 2,
                'backups'     => 2,
                'is_featured' => true,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'pro',
                'description' => 'High-performance tier for serious game servers, high-traffic bots, and production apps.',
                'price'       => 120.00,
                'memory'      => 10240,
                'cpu'         => 200,
                'disk'        => 40960,
                'io'          => 500,
                'databases'   => 3,
                'backups'     => 3,
                'is_featured' => false,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'unlimited',
                'description' => 'No limits. Allocate as much RAM, CPU, and disk as the node can give. Best for power users.',
                'price'       => 150.00,
                'memory'      => 0,
                'cpu'         => 0,
                'disk'        => 0,
                'io'          => 500,
                'databases'   => 5,
                'backups'     => 5,
                'is_featured' => false,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('plans')->delete();
    }
};
