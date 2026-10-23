<?php

namespace Tests\Feature;

use App\Enums\BankDepositStatus;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BankDepositIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_secure_sepay_bank_account_configuration(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/bank-accounts', [
            'bank_code' => 'VCB',
            'account_number' => '1234567890',
            'account_holder' => 'SHOPKEY COMPANY',
            'branch' => 'Ho Chi Minh',
            'deposit_prefix' => 'SKN',
            'minimum_deposit' => 10000,
            'maximum_deposit' => 5000000,
            'is_visible' => '1',
            'auto_confirm' => '1',
            'provider' => 'sepay',
            'webhook_auth' => 'hmac',
            'api_token' => 'sepay-bearer-token-secret',
            'webhook_secret' => 'sepay-hmac-secret-test',
        ]);

        $account = BankAccount::query()->firstOrFail();

        $response->assertRedirect(route('admin.bank-accounts.edit', $account));
        $this->assertSame('Vietcombank', $account->bank_name);
        $this->assertSame('sepay-bearer-token-secret', $account->api_token);
        $this->assertSame('sepay-hmac-secret-test', $account->webhook_secret);
        $this->assertStringNotContainsString('sepay-bearer-token-secret', (string) DB::table('bank_accounts')->value('api_token'));
        $this->assertStringNotContainsString('sepay-hmac-secret-test', (string) DB::table('bank_accounts')->value('webhook_secret'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'admin.bank_account.created', 'actor_id' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.bank-accounts.edit', $account))
            ->assertOk()
            ->assertSee('/api/payment/sepay/webhook/'.$account->webhook_key)
            ->assertDontSee('sepay-bearer-token-secret')
            ->assertDontSee('sepay-hmac-secret-test');

        $this->actingAs($admin)->put(route('admin.bank-accounts.update', $account), [
            'bank_code' => 'VCB',
            'account_number' => '1234567890',
            'account_holder' => 'SHOPKEY COMPANY',
            'branch' => 'Ha Noi',
            'deposit_prefix' => 'SKN',
            'minimum_deposit' => 10000,
            'maximum_deposit' => 5000000,
            'is_visible' => '1',
            'auto_confirm' => '1',
            'provider' => 'sepay',
            'webhook_auth' => 'hmac',
            'api_token' => '',
            'webhook_secret' => '',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $account->refresh();
        $this->assertSame('Ha Noi', $account->branch);
        $this->assertSame('sepay-bearer-token-secret', $account->api_token);
        $this->assertSame('sepay-hmac-secret-test', $account->webhook_secret);
    }

    public function test_exact_hmac_webhook_credits_wallet_once_even_when_retried(): void
    {
        [$user, $account, $deposit] = $this->invoice(100000);
        $payload = $this->payload($account, $deposit, 'SEPAY-1001', 100000);

        $this->signedWebhook($account, $payload)->assertOk()->assertExactJson(['success' => true]);
        $this->signedWebhook($account, $payload)->assertOk()->assertExactJson(['success' => true]);

        $this->assertSame(100000, $user->wallet->fresh()->balance);
        $this->assertDatabaseCount('bank_transactions', 1);
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertDatabaseHas('bank_deposits', [
            'id' => $deposit->id,
            'status' => BankDepositStatus::Paid->value,
            'received_amount' => 100000,
            'sepay_transaction_id' => 'SEPAY-1001',
        ]);
    }

    public function test_wrong_amount_waits_for_admin_review_then_credits_received_amount_once(): void
    {
        [$user, $account, $deposit] = $this->invoice(100000);

        $this->signedWebhook($account, $this->payload($account, $deposit, 'SEPAY-2001', 70000))
            ->assertOk();

        $this->assertSame(0, $user->wallet->fresh()->balance);
        $this->assertDatabaseHas('bank_deposits', [
            'id' => $deposit->id,
            'status' => BankDepositStatus::Review->value,
            'received_amount' => 70000,
        ]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post('/admin/bank-deposits/'.$deposit->id.'/approve', [
            'note' => 'Đã đối soát sao kê ngân hàng',
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/bank-deposits/'.$deposit->id.'/approve', [
            'note' => 'Kiểm tra idempotency lần hai',
        ])->assertSessionHasNoErrors();

        $this->assertSame(70000, $user->wallet->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_invalid_signature_or_wrong_receiving_account_never_credits_wallet(): void
    {
        [$user, $account, $deposit] = $this->invoice(120000);
        $payload = $this->payload($account, $deposit, 'SEPAY-3001', 120000);

        $this->rawWebhook($account, $payload, 'sha256='.str_repeat('0', 64), (string) now()->timestamp)
            ->assertUnauthorized()
            ->assertExactJson(['success' => false]);

        $payload['id'] = 'SEPAY-3002';
        $payload['accountNumber'] = '9999999999';
        $this->signedWebhook($account, $payload)->assertOk();

        $this->assertSame(0, $user->wallet->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
        $this->assertDatabaseHas('bank_deposits', ['id' => $deposit->id, 'status' => BankDepositStatus::Pending->value]);
    }

    public function test_customer_can_create_invoice_and_only_owner_can_open_qr_page(): void
    {
        $user = $this->customer();
        $other = $this->customer();
        $account = $this->account();

        $this->actingAs($user)->post('/wallet/deposit/bank', [
            'bank_account_id' => $account->id,
            'amount' => 250000,
        ])->assertRedirect();

        $deposit = BankDeposit::query()->firstOrFail();
        $this->actingAs($user)->get(route('wallet.deposit.bank.show', $deposit))
            ->assertOk()
            ->assertSee('https://vietqr.app/img?')
            ->assertSee('bank=970436')
            ->assertSee($deposit->invoice_code)
            ->assertSee('250.000₫');

        $this->actingAs($other)->get(route('wallet.deposit.bank.show', $deposit))->assertNotFound();
    }

    /** @return array{User, BankAccount, BankDeposit} */
    private function invoice(int $amount): array
    {
        $user = $this->customer();
        $account = $this->account();

        $this->actingAs($user)->post('/wallet/deposit/bank', [
            'bank_account_id' => $account->id,
            'amount' => $amount,
        ])->assertSessionHasNoErrors();

        return [$user, $account, BankDeposit::query()->firstOrFail()];
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 0, 'currency' => 'VND']);

        return $user->load('wallet');
    }

    private function account(): BankAccount
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

    /** @return array<string, mixed> */
    private function payload(BankAccount $account, BankDeposit $deposit, string $id, int $amount): array
    {
        return [
            'id' => $id,
            'gateway' => 'Vietcombank',
            'transactionDate' => '2026-08-21 12:00:00',
            'accountNumber' => $account->account_number,
            'subAccount' => null,
            'code' => $deposit->invoice_code,
            'content' => 'NAP TIEN '.$deposit->invoice_code,
            'transferType' => 'in',
            'transferAmount' => $amount,
            'accumulated' => $amount,
            'referenceCode' => 'FT'.substr(hash('sha256', $id), 0, 12),
        ];
    }

    private function signedWebhook(BankAccount $account, array $payload)
    {
        $timestamp = (string) now()->timestamp;
        $raw = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$raw, $account->webhook_secret);

        return $this->rawWebhook($account, $payload, $signature, $timestamp, $raw);
    }

    private function rawWebhook(BankAccount $account, array $payload, string $signature, string $timestamp, ?string $raw = null)
    {
        $raw ??= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this->call('POST', '/api/payment/sepay/webhook/'.$account->webhook_key, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SEPAY_TIMESTAMP' => $timestamp,
            'HTTP_X_SEPAY_SIGNATURE' => $signature,
        ], $raw);
    }
}
