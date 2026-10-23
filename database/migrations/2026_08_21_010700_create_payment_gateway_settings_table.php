<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway', 50)->unique();
            $table->boolean('is_enabled')->default(false);
            $table->string('provider_url', 2048)->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->string('partner_id')->nullable();
            $table->text('partner_key')->nullable();
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};
