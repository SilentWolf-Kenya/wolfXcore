<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wxn_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('type', 20)->default('info');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wxn_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('notification_id');
            $table->timestamp('read_at')->useCurrent();
            $table->unique(['user_id', 'notification_id']);
            $table->foreign('notification_id')->references('id')->on('wxn_notifications')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wxn_notification_reads');
        Schema::dropIfExists('wxn_notifications');
    }
};
