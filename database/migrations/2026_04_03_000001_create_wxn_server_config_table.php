<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wxn_server_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('node_id')->default(1);
            $table->unsignedInteger('nest_id')->default(5);
            $table->unsignedInteger('egg_id')->default(16);
            $table->string('startup_override', 500)->nullable();
            $table->string('docker_image_override', 255)->nullable();
            $table->timestamps();
        });

        DB::table('wxn_server_config')->insert([
            'node_id'    => 1,
            'nest_id'    => 5,
            'egg_id'     => 16,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('wxn_server_config');
    }
};
