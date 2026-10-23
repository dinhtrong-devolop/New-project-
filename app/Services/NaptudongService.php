<?php

namespace App\Services;

use App\Enums\CardDepositStatus;
use App\Models\CardDeposit;
use App\Models\PaymentGatewaySetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NaptudongService
{
    public const GATEWAY = 'naptudong';

    private bool $runtimeSettingLoaded = false;

    private ?PaymentGatewaySetting $runtimeSetting = null;

    public function configured(): bool
    {
        return $this->enabled()
            && filled($this->partnerId())
            && filled($this->partnerKey())
            && filled($this->providerUrl());
    }

    public function enabled(): bool
    {
        $setting = $this->setting();

        if ($setting) {
            return $setting->is_enabled;
        }

        return filled(config('services.naptudong.partner_id'))
            && filled(config('services.naptudong.partner_key'))
            && filled(config('services.naptudong.url'));
    }

    public function discountPercent(): float
    {
        return (float) ($this->setting()?->discount_percent ?? 0);
    }

    /** @return array{is_enabled: bool, provider_url: string, partner_id: string, has_partner_key: bool, discount_percent: float, source: string} */
    public function adminConfiguration(): array
    {
        return [
            'is_enabled' => $this->enabled(),
            'provider_url' => $this->providerUrl(),
            'partner_id' => $this->partnerId(),
            'has_partner_key' => filled($this->partnerKey()),
            'discount_percent' => $this->discountPercent(),
            'source' => $this->setting() ? 'database' : 'environment',
        ];
    }

    public function forgetRuntimeSetting(): void
    {
        $this->runtimeSettingLoaded = false;
        $this->runtimeSetting = null;
    }

    /** @return array<string, mixed> */
    public function submit(CardDeposit $deposit, string $code, string $serial): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Cổng Naptudong chưa được cấu hình thông tin đối tác.');
        }

        $command = 'charging';
        $payload = [
            'telco' => $deposit->provider,
            'code' => $code,
            'serial' => $serial,
            'amount' => $deposit->declared_amount,
            'request_id' => $deposit->request_id,
            'partner_id' => $this->partnerId(),
            'command' => $command,
            'sign' => $this->requestSignature($code, $command, $deposit->request_id, $serial, $deposit->provider),
        ];

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) config('services.naptudong.timeout', 15))
                ->retry(2, 300, throw: false)
                ->post($this->providerUrl(), $payload);

            if (! $response->successful() || ! is_array($response->json())) {
                throw new RuntimeException('Naptudong trả về phản hồi không hợp lệ (HTTP '.$response->status().').');
            }

            return $response->json();
        } catch (ConnectionException $exception) {
            Log::warning('Naptudong connection failed.', ['request_id' => $deposit->request_id, 'message' => $exception->getMessage()]);
            throw new RuntimeException('Không thể kết nối cổng nạp thẻ. Vui lòng thử lại sau.', previous: $exception);
        }
    }

    public function requestSignature(string $code, string $command, string $requestId, string $serial, string $telco): string
    {
        return md5(
            $this->partnerKey()
            .$code
            .$command
            .$this->partnerId()
            .$requestId
            .$serial
            .strtoupper($telco)
        );
    }

    public function callbackSignature(string $code, string $serial): string
    {
        return md5($this->partnerKey().$code.$serial);
    }

    /** @param array<string, mixed> $payload */
    public function verifyCallback(array $payload): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $provided = strtolower((string) ($payload['callback_sign'] ?? ''));

        return $provided !== '' && hash_equals(
            $this->callbackSignature((string) ($payload['code'] ?? ''), (string) ($payload['serial'] ?? '')),
            $provided
        );
    }

    public function mapStatus(int|string|null $status): CardDepositStatus
    {
        return match ((int) $status) {
            99 => CardDepositStatus::Pending,
            1 => CardDepositStatus::Success,
            2 => CardDepositStatus::WrongAmount,
            4 => CardDepositStatus::Maintenance,
            default => CardDepositStatus::Failed,
        };
    }

    private function setting(): ?PaymentGatewaySetting
    {
        if (! $this->runtimeSettingLoaded) {
            $this->runtimeSetting = PaymentGatewaySetting::query()->where('gateway', self::GATEWAY)->first();
            $this->runtimeSettingLoaded = true;
        }

        return $this->runtimeSetting;
    }

    private function providerUrl(): string
    {
        return (string) ($this->setting()?->provider_url ?: config('services.naptudong.url'));
    }

    private function partnerId(): string
    {
        return (string) ($this->setting()?->partner_id ?: config('services.naptudong.partner_id'));
    }

    private function partnerKey(): string
    {
        return (string) ($this->setting()?->partner_key ?: config('services.naptudong.partner_key'));
    }
}
