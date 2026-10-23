<?php

namespace Tests\Feature;

use App\Enums\CardDepositStatus;
use App\Models\CardDeposit;
use App\Models\PaymentGatewaySetting;
use App\Models\User;
use App\Services\NaptudongService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NaptudongIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.naptudong', [
            'partner_id' => 'partner-test',
            'partner_key' => 'secret-test-key',
            'url' => 'https://naptudong.test/chargingws/v2',
            'callback_url' => 'https://shopkey.test/api/payment/naptudong/callback',
            'timeout' => 5,
            'providers' => ['VIETTEL', 'VINAPHONE', 'MOBIFONE'],
            'denominations' => [50_000, 100_000, 200_000],
        ]);
    }

    public function test_pending_submission_and_success_callback_credit_wallet_only_once(): void
    {
        $user = $this->customer();
        $deposit = $this->submitCard($user, '12345678901234', '987654321098765', 100_000);
        $payload = $this->callbackPayload($deposit, 1, 100_000, 78_000);

        $this->postJson('/api/payment/naptudong/callback', $payload)
            ->assertOk()->assertJsonPath('data.status', 'success');
        $this->postJson('/api/payment/naptudong/callback', $payload)
            ->assertOk()->assertJsonPath('data.status', 'success');

        $this->assertSame(78_000, $user->wallet->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertDatabaseHas('card_deposits', [
            'id' => $deposit->id,
            'status' => CardDepositStatus::Success->value,
            'received_amount' => 78_000,
            'encrypted_card_code' => null,
        ]);
    }

    public function test_invalid_callback_signature_is_rejected_without_credit(): void
    {
        $user = $this->customer();
        $deposit = $this->submitCard($user, '12345678901234', '987654321098765', 100_000);
        $payload = $this->callbackPayload($deposit, 1, 100_000, 78_000);
        $payload['callback_sign'] = str_repeat('0', 32);

        $this->postJson('/api/payment/naptudong/callback', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertSame(0, $user->wallet->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_wrong_denomination_credits_only_provider_received_amount(): void
    {
        $user = $this->customer();
        $deposit = $this->submitCard($user, '12345678901234', '987654321098765', 100_000);

        $this->postJson('/api/payment/naptudong/callback', $this->callbackPayload($deposit, 2, 50_000, 35_000))
            ->assertOk()->assertJsonPath('data.status', 'wrong_amount');

        $this->assertSame(35_000, $user->wallet->fresh()->balance);
        $this->assertDatabaseHas('card_deposits', ['id' => $deposit->id, 'actual_amount' => 50_000, 'received_amount' => 35_000]);
    }

    public function test_failed_card_never_credits_wallet(): void
    {
        $user = $this->customer();
        $deposit = $this->submitCard($user, '12345678901234', '987654321098765', 100_000);

        $this->postJson('/api/payment/naptudong/callback', $this->callbackPayload($deposit, 3, 0, 0))
            ->assertOk()->assertJsonPath('data.status', 'failed');

        $this->assertSame(0, $user->wallet->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_database_gateway_setting_can_disable_and_override_environment_configuration(): void
    {
        $setting = PaymentGatewaySetting::query()->create([
            'gateway' => 'naptudong',
            'is_enabled' => false,
            'provider_url' => 'https://runtime-gateway.test/chargingws/v2',
            'discount_percent' => 20,
            'partner_id' => 'runtime-partner',
            'partner_key' => 'runtime-secret-key',
        ]);

        $this->assertFalse(app(NaptudongService::class)->configured());

        $setting->update(['is_enabled' => true]);
        $provider = app(NaptudongService::class);

        $this->assertTrue($provider->configured());
        $this->assertSame(20.0, $provider->discountPercent());
        $this->assertSame(
            md5('runtime-secret-key'.'card-code'.'charging'.'runtime-partner'.'request-1'.'serial-1'.'VIETTEL'),
            $provider->requestSignature('card-code', 'charging', 'request-1', 'serial-1', 'VIETTEL'),
        );
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 0, 'currency' => 'VND']);
        $user->cart()->create();

        return $user->load('wallet');
    }

    private function submitCard(User $user, string $serial, string $code, int $amount): CardDeposit
    {
        Http::fake([
            'https://naptudong.test/*' => Http::response([
                'status' => 99,
                'message' => 'Thẻ đang chờ xử lý',
                'request_id' => 'provider-echo',
                'trans_id' => 12345,
            ]),
        ]);

        $this->actingAs($user)
            ->post('/wallet/deposit/card', compact('serial', 'code', 'amount') + ['provider' => 'VIETTEL'])
            ->assertRedirect('/wallet/deposits');

        Http::assertSent(fn ($request) => $request['command'] === 'charging'
            && $request['partner_id'] === 'partner-test'
            && $request['sign'] === app(NaptudongService::class)->requestSignature($code, 'charging', $request['request_id'], $serial, 'VIETTEL'));

        return CardDeposit::query()->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function callbackPayload(CardDeposit $deposit, int $status, int $value, int $amount): array
    {
        $code = '987654321098765';
        $serial = '12345678901234';

        return [
            'status' => $status,
            'message' => $status === 1 ? 'Thẻ đúng' : 'Kết quả xử lý',
            'value' => $value,
            'amount' => $amount,
            'code' => $code,
            'serial' => $serial,
            'request_id' => $deposit->request_id,
            'telco' => 'VIETTEL',
            'trans_id' => 'NTD-123',
            'callback_sign' => app(NaptudongService::class)->callbackSignature($code, $serial),
        ];
    }
}
