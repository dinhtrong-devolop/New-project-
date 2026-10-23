<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0);
            $table->char('currency', 3)->default('VND');
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 30)->index();
            $table->bigInteger('amount');
            $table->unsignedBigInteger('balance_before');
            $table->unsignedBigInteger('balance_after');
            $table->nullableMorphs('reference');
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->string('description')->nullable();
            $table->string('status', 20)->default('completed')->index();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('card_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('request_id', 40)->unique();
            $table->string('provider', 30)->index();
            $table->char('serial_hash', 64)->index();
            $table->string('serial_last_four', 4)->nullable();
            $table->char('card_code_hash', 64);
            $table->text('encrypted_card_code')->nullable();
            $table->unsignedBigInteger('declared_amount');
            $table->unsignedBigInteger('actual_amount')->nullable();
            $table->unsignedBigInteger('received_amount')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('provider_transaction_id')->nullable()->index();
            $table->string('provider_message')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_deposits');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
