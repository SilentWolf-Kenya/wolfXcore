<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wxn_bot_repos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('repo_url');
            $table->string('git_address');
            $table->string('main_file')->default('index.js');
            $table->text('app_json_raw')->nullable();
            $table->text('env_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wxn_bot_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repo_id');
            $table->unsignedInteger('server_id')->nullable();
            $table->string('server_uuid', 36)->nullable();
            $table->enum('status', ['available', 'assigned', 'provisioning', 'error'])->default('provisioning');
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
            $table->foreign('repo_id')->references('id')->on('wxn_bot_repos')->onDelete('cascade');
        });

        Schema::create('wxn_bot_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('repo_id');
            $table->string('server_uuid', 36);
            $table->text('configs')->nullable();
            $table->enum('status', ['pending', 'configured', 'starting', 'running'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wxn_bot_configs');
        Schema::dropIfExists('wxn_bot_allocations');
        Schema::dropIfExists('wxn_bot_repos');
    }
};
