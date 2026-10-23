<?php

namespace Database\Seeders;

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Enums\VoucherType;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Seeder;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Khách hàng Demo',
                'role' => UserRole::Customer,
                'password' => 'password',
                'email_verified_at' => now(),
                'locked_at' => null,
            ]
        );

        $wallet = $customer->wallet()->firstOrCreate([], ['balance' => 2_000_000, 'currency' => 'VND']);
        $customer->cart()->firstOrCreate();

        if ($wallet->balance === 2_000_000 && ! $wallet->transactions()->where('idempotency_key', 'seed:demo-wallet')->exists()) {
            $wallet->transactions()->create([
                'user_id' => $customer->id,
                'type' => WalletTransactionType::AdminAdjustment,
                'amount' => 2_000_000,
                'balance_before' => 0,
                'balance_after' => 2_000_000,
                'idempotency_key' => 'seed:demo-wallet',
                'description' => 'Số dư thử nghiệm cho môi trường phát triển',
                'status' => TransactionStatus::Completed,
            ]);
        }

        Voucher::query()->updateOrCreate(['code' => 'WELCOME10'], [
            'type' => VoucherType::Percentage,
            'discount_value' => 10,
            'minimum_order_amount' => 100_000,
            'maximum_discount' => 100_000,
            'usage_limit' => 500,
            'per_user_usage_limit' => 1,
            'is_active' => true,
        ]);

        Voucher::query()->updateOrCreate(['code' => 'SHOPKEY50'], [
            'type' => VoucherType::FixedAmount,
            'discount_value' => 50_000,
            'minimum_order_amount' => 300_000,
            'usage_limit' => 200,
            'per_user_usage_limit' => 2,
            'is_active' => true,
        ]);
    }
}
