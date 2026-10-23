<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('webhook_key', 26)->unique();
            $table->string('provider', 30)->default('sepay');
            $table->string('bank_code', 30);
            $table->string('bank_name', 100);
            $table->string('account_number', 100);
            $table->string('account_holder', 255);
            $table->string('branch')->nullable();
            $table->string('deposit_prefix', 12)->default('SKN');
            $table->unsignedBigInteger('minimum_deposit')->default(10000);
            $table->unsignedBigInteger('maximum_deposit')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('auto_confirm')->default(true);
            $table->string('webhook_auth', 20)->default('hmac');
            $table->text('api_token')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['provider', 'account_number']);
            $table->index(['is_visible', 'auto_confirm']);
        });

        Schema::create('bank_deposits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->restrictOnDelete();
            $table->string('invoice_code', 40)->unique();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('received_amount')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->string('sepay_transaction_id')->nullable()->unique();
            $table->string('reference_code')->nullable();
            $table->text('transfer_content')->nullable();
            $table->json('provider_payload')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['bank_account_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('bank_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_deposit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30)->default('sepay');
            $table->string('provider_transaction_id');
            $table->string('gateway', 100);
            $table->timestamp('transaction_at')->nullable();
            $table->string('account_number', 100)->nullable();
            $table->string('sub_account')->nullable();
            $table->string('payment_code')->nullable()->index();
            $table->string('transfer_type', 10);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('accumulated')->default(0);
            $table->text('content')->nullable();
            $table->string('reference_code')->nullable();
            $table->json('provider_payload');
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_transaction_id']);
            $table->index(['account_number', 'transaction_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_deposits');
        Schema::dropIfExists('bank_accounts');
    }
};
