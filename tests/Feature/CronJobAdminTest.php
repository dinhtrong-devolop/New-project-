<?php

namespace Tests\Feature;

use App\Enums\BankDepositStatus;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\CronJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CronJobAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_toggle_cron_jobs(): void
    {
        $admin = User::factory()->admin()->create();
        $job = CronJob::bankDepositSync();

        $this->actingAs($admin)->get('/admin/cron-jobs')
            ->assertOk()
            ->assertSee('Quản lý Cron Jobs')
            ->assertSee('Nạp tiền Bank')
            ->assertSee('Chưa cài')
            ->assertSee('* * * * *')
            ->assertSee('Mỗi 1 phút');

        $this->actingAs($admin)
            ->patch(route('admin.cron-jobs.toggle', $job))
            ->assertSessionHasNoErrors();

        $this->assertFalse($job->fresh()->is_enabled);
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $admin->id,
            'action' => 'admin.cron_job.toggled',
        ]);
    }

    public function test_scheduler_heartbeat_and_manual_run_are_recorded_separately(): void
    {
        $job = CronJob::bankDepositSync();

        $this->assertSame(0, Artisan::call('shopkey:sync-bank-transactions', ['--source' => 'scheduler']));
        $job->refresh();

        $this->assertNotNull($job->last_scheduler_ping_at);
        $this->assertSame('healthy', $job->schedulerState());
        $this->assertSame('scheduler', $job->last_run_source);
        $this->assertSame('success', $job->last_status);

        $previousPing = $job->last_scheduler_ping_at->copy();
        $this->assertSame(0, Artisan::call('shopkey:sync-bank-transactions', ['--source' => 'manual']));
        $job->refresh();

        $this->assertSame('manual', $job->last_run_source);
        $this->assertTrue($job->last_scheduler_ping_at->equalTo($previousPing));
        $this->assertSame(2, $job->run_count);
    }

    public function test_bank_sync_fetches_sepay_transactions_and_credits_matching_invoice_once(): void
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 0, 'currency' => 'VND']);
        $account = $this->bankAccount();
        $deposit = BankDeposit::query()->create([
            'user_id' => $user->id,
            'bank_account_id' => $account->id,
            'invoice_code' => 'SKN'.$user->id.'CRONTEST',
            'amount' => 150000,
            'status' => BankDepositStatus::Pending,
            'expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'https://userapi.sepay.vn/v2/transactions*' => Http::response([
                'data' => [[
                    'id' => 'SEPAY-CRON-1001',
                    'bank_brand_name' => 'Vietcombank',
                    'transaction_date' => '2026-08-21 12:00:00',
                    'account_number' => $account->account_number,
                    'amount_in' => 150000,
                    'amount_out' => 0,
                    'transaction_content' => 'NAP TIEN '.$deposit->invoice_code,
                    'reference_number' => 'FTCRON1001',
                    'code' => $deposit->invoice_code,
                ]],
            ]),
        ]);

        $this->assertSame(0, Artisan::call('shopkey:sync-bank-transactions', ['--source' => 'manual']));
        $this->assertSame(0, Artisan::call('shopkey:sync-bank-transactions', ['--source' => 'manual']));

        $this->assertSame(150000, $user->wallet->fresh()->balance);
        $this->assertDatabaseCount('bank_transactions', 1);
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertDatabaseHas('bank_deposits', [
            'id' => $deposit->id,
            'status' => BankDepositStatus::Paid->value,
            'received_amount' => 150000,
            'sepay_transaction_id' => 'SEPAY-CRON-1001',
        ]);
        $this->assertDatabaseHas('cron_jobs', [
            'key' => CronJob::BANK_DEPOSIT_SYNC,
            'last_status' => 'success',
            'run_count' => 2,
        ]);
        Http::assertSentCount(2);
    }

    public function test_admin_can_run_cron_job_manually(): void
    {
        $admin = User::factory()->admin()->create();
        $job = CronJob::bankDepositSync();

        $this->actingAs($admin)
            ->post(route('admin.cron-jobs.run', $job))
            ->assertSessionHasNoErrors();

        $this->assertSame('manual', $job->fresh()->last_run_source);
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $admin->id,
            'action' => 'admin.cron_job.ran_manually',
        ]);
    }

    private function bankAccount(): BankAccount
    {
        return BankAccount::query()->create([
            'webhook_key' => (string) Str::ulid(),
            'provider' => 'sepay',
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => '1234567890',
            'account_holder' => 'SHOPKEY COMPANY',
            'deposit_prefix' => 'SKN',
            'minimum_deposit' => 10000,
            'maximum_deposit' => 10000000,
            'is_visible' => true,
            'auto_confirm' => true,
            'webhook_auth' => 'hmac',
            'api_token' => 'sepay-api-token-test',
            'webhook_secret' => 'sepay-webhook-secret-test',
        ]);
    }
}
