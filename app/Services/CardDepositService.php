<?php

namespace App\Services;

use App\Enums\CardDepositStatus;
use App\Enums\WalletTransactionType;
use App\Models\CardDeposit;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CardDepositService
{
    public function __construct(
        private readonly NaptudongService $provider,
        private readonly WalletService $wallets,
    ) {}

    public function submit(User $user, string $telco, int $amount, string $serial, string $code): CardDeposit
    {
        if (! $this->provider->configured()) {
            throw ValidationException::withMessages(['deposit' => 'Cổng Naptudong đang tắt hoặc chưa đủ thông tin. Quản trị viên cần kiểm tra mục Cài đặt nạp thẻ.']);
        }

        $deposit = CardDeposit::query()->create([
            'user_id' => $user->id,
            'request_id' => $this->requestId(),
            'provider' => strtoupper($telco),
            'serial_hash' => hash('sha256', $serial),
            'serial_last_four' => mb_substr($serial, -4),
            'card_code_hash' => hash('sha256', $code),
            'encrypted_card_code' => $code,
            'declared_amount' => $amount,
            'status' => CardDepositStatus::Pending,
        ]);

        try {
            $response = $this->provider->submit($deposit, $code, $serial);
            $mapped = $this->provider->mapStatus($response['status'] ?? null);

            $deposit->update([
                'status' => $mapped,
                'provider_transaction_id' => isset($response['trans_id']) ? (string) $response['trans_id'] : null,
                'provider_message' => mb_substr((string) ($response['message'] ?? 'Đã gửi thẻ, đang chờ xử lý.'), 0, 255),
                'provider_metadata' => $this->safeMetadata($response),
                'submitted_at' => now(),
                'encrypted_card_code' => in_array($mapped, [CardDepositStatus::Failed, CardDepositStatus::Maintenance], true) ? null : $code,
                'processed_at' => in_array($mapped, [CardDepositStatus::Failed, CardDepositStatus::Maintenance], true) ? now() : null,
            ]);
        } catch (RuntimeException $exception) {
            $deposit->update([
                'provider_message' => mb_substr($exception->getMessage(), 0, 255),
                'submitted_at' => now(),
            ]);
            Log::warning('Card submission failed.', ['request_id' => $deposit->request_id, 'message' => $exception->getMessage()]);

            throw ValidationException::withMessages(['deposit' => $exception->getMessage()]);
        }

        return $deposit->fresh();
    }

    /** @param array<string, mixed> $payload */
    public function processCallback(array $payload): CardDeposit
    {
        if (! $this->provider->verifyCallback($payload)) {
            Log::warning('Naptudong callback signature rejected.', ['request_id' => $payload['request_id'] ?? null]);
            throw ValidationException::withMessages(['signature' => 'Chữ ký callback không hợp lệ.']);
        }

        return DB::transaction(function () use ($payload): CardDeposit {
            $deposit = CardDeposit::query()
                ->where('request_id', (string) $payload['request_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! hash_equals($deposit->card_code_hash, hash('sha256', (string) $payload['code']))
                || ! hash_equals($deposit->serial_hash, hash('sha256', (string) $payload['serial']))) {
                throw ValidationException::withMessages(['card' => 'Thông tin callback không khớp yêu cầu gốc.']);
            }

            if ($deposit->processed_at && in_array($deposit->status, [CardDepositStatus::Success, CardDepositStatus::WrongAmount], true)) {
                return $deposit;
            }

            $status = $this->provider->mapStatus($payload['status'] ?? null);
            $actual = max(0, (int) ($payload['value'] ?? 0));
            $received = max(0, (int) ($payload['amount'] ?? 0));
            $payable = in_array($status, [CardDepositStatus::Success, CardDepositStatus::WrongAmount], true) && $received > 0;

            if ($payable) {
                $this->wallets->change(
                    $deposit->user,
                    $received,
                    WalletTransactionType::Deposit,
                    'deposit:'.$deposit->request_id,
                    'Nạp thẻ '.$deposit->provider.' - '.$deposit->request_id,
                    $deposit,
                );
            }

            $deposit->update([
                'actual_amount' => $actual ?: null,
                'received_amount' => $received ?: null,
                'status' => $status,
                'provider_transaction_id' => isset($payload['trans_id']) ? (string) $payload['trans_id'] : $deposit->provider_transaction_id,
                'provider_message' => mb_substr((string) ($payload['message'] ?? ''), 0, 255) ?: null,
                'provider_metadata' => $this->safeMetadata($payload),
                'processed_at' => $status === CardDepositStatus::Pending ? null : now(),
                'encrypted_card_code' => $status === CardDepositStatus::Pending ? $deposit->encrypted_card_code : null,
            ]);

            return $deposit->fresh();
        }, 3);
    }

    private function requestId(): string
    {
        do {
            $id = 'SK'.now()->format('Ymd').Str::upper(Str::random(16));
        } while (CardDeposit::query()->where('request_id', $id)->exists());

        return $id;
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function safeMetadata(array $payload): array
    {
        return Arr::except($payload, ['code', 'serial', 'sign', 'callback_sign']);
    }
}
