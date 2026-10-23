<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name', 120);
            $table->string('command', 180);
            $table->string('expression', 80);
            $table->string('frequency_label', 120);
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->string('last_status', 20)->nullable()->index();
            $table->string('last_run_source', 20)->nullable();
            $table->text('last_message')->nullable();
            $table->unsignedInteger('last_duration_ms')->nullable();
            $table->unsignedBigInteger('run_count')->default(0);
            $table->unsignedBigInteger('failure_count')->default(0);
            $table->timestamp('last_scheduler_ping_at')->nullable()->index();
            $table->timestamp('last_started_at')->nullable();
            $table->timestamp('last_finished_at')->nullable();
            $table->timestamp('last_succeeded_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamps();
        });

        DB::table('cron_jobs')->insert([
            'key' => 'bank-deposit-sync',
            'name' => 'Nạp tiền Bank',
            'command' => 'shopkey:sync-bank-transactions',
            'expression' => '* * * * *',
            'frequency_label' => 'Mỗi 1 phút',
            'description' => 'Tự động quét và cộng tiền từ giao dịch chuyển khoản ngân hàng.',
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_jobs');
    }
};
