<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_history_navigation_and_all_history_pages_render(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Lịch sử giao dịch')
            ->assertSee('Lịch sử đặt dịch vụ')
            ->assertSee('Lịch sử nạp thẻ cào')
            ->assertSee('Lịch sử dùng mã giảm giá');

        foreach ([
            '/admin/history/transactions' => 'Lịch sử giao dịch',
            '/admin/history/orders' => 'Lịch sử đặt dịch vụ',
            '/admin/history/card-deposits' => 'Lịch sử nạp thẻ cào',
            '/admin/history/voucher-usages' => 'Lịch sử dùng mã giảm giá',
        ] as $url => $heading) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee($heading);
        }
    }

    public function test_admin_can_search_wallet_transaction_history(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create([
            'name' => 'History Customer',
            'email' => 'history@shopkey.test',
        ]);
        $wallet = $customer->wallet()->create(['balance' => 125000, 'currency' => 'VND']);

        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $customer->id,
            'type' => WalletTransactionType::Deposit,
            'amount' => 125000,
            'balance_before' => 0,
            'balance_after' => 125000,
            'idempotency_key' => 'history-test-transaction',
            'description' => 'Nạp tiền kiểm thử lịch sử',
            'status' => TransactionStatus::Completed,
        ]);

        $this->actingAs($admin)
            ->get('/admin/history/transactions?search=history%40shopkey.test&type=deposit')
            ->assertOk()
            ->assertSee('History Customer')
            ->assertSee('Nạp tiền kiểm thử lịch sử')
            ->assertSee('+125.000₫');
    }
}
