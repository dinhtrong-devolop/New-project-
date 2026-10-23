<?php

namespace App\Services;

use App\Enums\BankDepositStatus;
use App\Enums\WalletTransactionType;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\BankTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BankDepositService
{
    public function __construct(private readonly WalletService $wallets) {}

    public function createInvoice(User $user, BankAccount $account, int $amount): BankDeposit
    {
        if (! $account->is_visible) {
            throw ValidationException::withMessages(['bank_account_id' => 'Tài khoản ngân hàng này hiện không nhận tiền.']);
        }

        if ($amount < $account->minimum_deposit) {
            throw ValidationException::withMessages(['amount' => 'Số tiền nạp tối thiểu là '.number_format($account->minimum_deposit, 0, ',', '.').'₫.']);
        }

        if ($account->maximum_deposit !== null && $amount > $account->maximum_deposit) {
            throw ValidationException::withMessages(['amount' => 'Số tiền nạp tối đa là '.number_format($account->maximum_deposit, 0, ',', '.').'₫.']);
        }

        do {
            $code = $account->deposit_prefix.$user->id.Str::upper(Str::random(8));
        } while (BankDeposit::query()->where('invoice_code', $code)->exists());

        return $account->deposits()->create([
            'user_id' => $user->id,
            'invoice_code' => $code,
            'amount' => $amount,
            'status' => BankDepositStatus::Pending,
            'expires_at' => now()->addHour(),
        ]);
    }

    /** @param array<string, mixed> $payload */
    public function processWebhook(BankAccount $account, array $payload): BankTransaction
    {
        return DB::transaction(function () use ($account, $payload): BankTransaction {
            $providerTransactionId = (string) $payload['id'];
            $transaction = BankTransaction::query()->firstOrCreate(
                [
                    'provider' => SepayService::PROVIDER,
                    'provider_transaction_id' => $providerTransactionId,
                ],
                [
                    'bank_account_id' => $account->id,
                    'gateway' => (string) $payload['gateway'],
                    'transaction_at' => $this->transactionDate($payload['transactionDate'] ?? null),
                    'account_number' => $payload['accountNumber'] ?? null,
                    'sub_account' => $payload['subAccount'] ?? null,
                    'payment_code' => $payload['code'] ?? null,
                    'transfer_type' => strtolower((string) $payload['transferType']),
                    'amount' => (int) $payload['transferAmount'],
                    'accumulated' => (int) ($payload['accumulated'] ?? 0),
                    'content' => $payload['content'] ?? null,
                    'reference_code' => $payload['referenceCode'] ?? null,
                    'provider_payload' => $payload,
                ],
            );

            if (! $transaction->wasRecentlyCreated || $transaction->transfer_type !== 'in') {
                return $transaction;
            }

            if ($this->normalizeAccount((string) $transaction->account_number) !== $this->normalizeAccount($account->account_number)) {
                return $transaction;
            }

            $deposit = $this->findInvoice($account, $payload);

            if (! $deposit) {
                return $transaction;
            }

            $deposit = BankDeposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->status === BankDepositStatus::Paid) {
                return $transaction;
            }

            $transaction->update([
                'bank_deposit_id' => $deposit->id,
                'matched_at' => now(),
            ]);

            $receivedAmount = (int) $transaction->amount;
            $requiresReview = $receivedAmount !== $deposit->amount
                || ! $account->auto_confirm
                || ($deposit->expires_at && $deposit->expires_at->isPast());

            $deposit->update([
                'received_amount' => $receivedAmount,
                'status' => $requiresReview ? BankDepositStatus::Review : BankDepositStatus::Paid,
                'sepay_transaction_id' => $providerTransactionId,
                'reference_code' => $transaction->reference_code,
                'transfer_content' => $transaction->content,
                'provider_payload' => $payload,
                'paid_at' => $requiresReview ? null : now(),
            ]);

            if (! $requiresReview) {
                $this->credit($deposit->fresh());
            }

            return $transaction->fresh();
        });
    }

    public function approve(BankDeposit $deposit, User $admin, string $note): BankDeposit
    {
        return DB::transaction(function () use ($deposit, $admin, $note): BankDeposit {
            $deposit = BankDeposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->status === BankDepositStatus::Paid) {
                return $deposit;
            }

            if ($deposit->received_amount === null || $deposit->received_amount < 1 || $deposit->sepay_transaction_id === null) {
                throw ValidationException::withMessages(['deposit' => 'Chỉ có thể duyệt hóa đơn đã nhận được giao dịch từ SePay.']);
            }

            $this->credit($deposit);
            $deposit->update([
                'status' => BankDepositStatus::Paid,
                'approved_by' => $admin->id,
                'admin_note' => $note,
                'paid_at' => now(),
            ]);

            return $deposit->fresh();
        });
    }

    private function credit(BankDeposit $deposit): void
    {
        $this->wallets->change(
            $deposit->user,
            (int) $deposit->received_amount,
            WalletTransactionType::Deposit,
            'bank-deposit:'.$deposit->invoice_code,
            'Nạp tiền tự động qua ngân hàng - '.$deposit->invoice_code,
            $deposit,
        );
    }

    /** @param array<string, mixed> $payload */
    private function findInvoice(BankAccount $account, array $payload): ?BankDeposit
    {
        $candidates = array_filter([
            isset($payload['code']) ? strtoupper(trim((string) $payload['code'])) : null,
            isset($payload['content']) ? strtoupper((string) $payload['content']) : null,
        ]);

        $pending = $account->deposits()
            ->whereIn('status', [BankDepositStatus::Pending->value, BankDepositStatus::Expired->value])
            ->latest()
            ->limit(250)
            ->get();

        return $pending->first(function (BankDeposit $deposit) use ($candidates): bool {
            foreach ($candidates as $candidate) {
                if (str_contains($candidate, strtoupper($deposit->invoice_code))) {
                    return true;
                }
            }

            return false;
        });
    }

    private function normalizeAccount(string $account): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $account));
    }

    private function transactionDate(mixed $date): ?Carbon
    {
        if (! is_string($date) || $date === '') {
            return null;
        }

        return Carbon::parse($date);
    }
}
