<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wxn_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->unique();
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('wxn_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->enum('type', ['credit', 'debit'])->default('credit');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2)->default(0.00);
            $table->string('description')->nullable();
            $table->string('reference')->nullable()->unique();
            $table->string('gateway')->nullable()->default('paystack');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wxn_wallet_transactions');
        Schema::dropIfExists('wxn_wallets');
    }
};
