<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wxn_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('gateway', 30)->default('paystack');
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('pending'); // pending, active, expired, cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });

        Schema::create('wxn_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('gateway', 30)->default('paystack');
            $table->string('reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('KES');
            $table->string('status', 20)->default('pending'); // pending, success, failed
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wxn_payments');
        Schema::dropIfExists('wxn_subscriptions');
    }
};
