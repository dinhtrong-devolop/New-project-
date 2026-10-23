<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function change(
        User $user,
        int $amount,
        WalletTransactionType $type,
        string $idempotencyKey,
        string $description,
        ?Model $reference = null,
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $amount, $type, $idempotencyKey, $description, $reference): WalletTransaction {
            $existing = WalletTransaction::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                return $existing;
            }

            $walletId = Wallet::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'currency' => 'VND']
            )->id;
            $wallet = Wallet::query()->whereKey($walletId)->lockForUpdate()->firstOrFail();

            // A concurrent request with the same key may have committed while this
            // request was waiting for the wallet lock.
            $existing = WalletTransaction::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                return $existing;
            }

            $before = $wallet->balance;
            $after = $before + $amount;

            if ($after < 0) {
                throw ValidationException::withMessages(['wallet' => 'Số dư ví không đủ để thanh toán đơn hàng.']);
            }

            $wallet->update(['balance' => $after]);

            return $wallet->transactions()->create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'status' => TransactionStatus::Completed,
            ]);
        });
    }
}
