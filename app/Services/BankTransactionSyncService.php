<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Support\Arr;
use RuntimeException;

class BankTransactionSyncService
{
    public function __construct(
        private readonly SepayService $sepay,
        private readonly BankDepositService $deposits,
    ) {}

    /** @return array{accounts:int, received:int, created:int, matched:int} */
    public function sync(): array
    {
        $summary = ['accounts' => 0, 'received' => 0, 'created' => 0, 'matched' => 0];
        $errors = [];

        $accounts = BankAccount::query()
            ->where('provider', SepayService::PROVIDER)
            ->whereNotNull('api_token')
            ->get();

        foreach ($accounts as $account) {
            $summary['accounts']++;

            try {
                $response = $this->sepay->transactions($account, [
                    'account_number' => $account->account_number,
                    'limit' => 100,
                ]);

                foreach ($this->extractTransactions($response) as $rawTransaction) {
                    $payload = $this->normalizeTransaction($account, $rawTransaction);

                    if ($payload === null) {
                        continue;
                    }

                    $summary['received']++;
                    $alreadyExists = BankTransaction::query()
                        ->where('provider', SepayService::PROVIDER)
                        ->where('provider_transaction_id', $payload['id'])
                        ->exists();

                    $transaction = $this->deposits->processWebhook($account, $payload);

                    if (! $alreadyExists) {
                        $summary['created']++;
                    }

                    if (! $alreadyExists && $transaction->bank_deposit_id !== null) {
                        $summary['matched']++;
                    }
                }
            } catch (\Throwable $exception) {
                report($exception);
                $errors[] = $account->bank_name.' ••••'.substr($account->account_number, -4).': '.$exception->getMessage();
            }
        }

        if ($errors !== []) {
            throw new RuntimeException(implode(' | ', $errors));
        }

        return $summary;
    }

    /** @return array<int, array<string, mixed>> */
    private function extractTransactions(array $response): array
    {
        if (array_is_list($response)) {
            return array_values(array_filter($response, 'is_array'));
        }

        $candidates = [
            $response['transactions'] ?? null,
            $response['data'] ?? null,
            Arr::get($response, 'data.transactions'),
            Arr::get($response, 'data.items'),
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && array_is_list($candidate)) {
                return array_values(array_filter($candidate, 'is_array'));
            }
        }

        return [];
    }

    /** @param array<string, mixed> $transaction
     * @return array<string, mixed>|null
     */
    private function normalizeTransaction(BankAccount $account, array $transaction): ?array
    {
        $id = $this->first($transaction, ['id', 'transaction_id', 'transactionId']);
        $amountIn = (int) $this->first($transaction, ['amount_in', 'amountIn'], 0);
        $amountOut = (int) $this->first($transaction, ['amount_out', 'amountOut'], 0);
        $transferType = strtolower((string) $this->first($transaction, ['transferType', 'transfer_type'], $amountIn > 0 ? 'in' : 'out'));
        $amount = (int) $this->first($transaction, ['transferAmount', 'transfer_amount'], $transferType === 'in' ? $amountIn : $amountOut);

        if ($id === null || $amount < 1 || ! in_array($transferType, ['in', 'out'], true)) {
            return null;
        }

        return [
            'id' => (string) $id,
            'gateway' => (string) $this->first($transaction, ['gateway', 'bank_brand_name', 'bank_name'], $account->bank_name),
            'transactionDate' => $this->first($transaction, ['transactionDate', 'transaction_date', 'transaction_at']),
            'accountNumber' => (string) $this->first($transaction, ['accountNumber', 'account_number'], $account->account_number),
            'subAccount' => $this->first($transaction, ['subAccount', 'sub_account']),
            'code' => $this->first($transaction, ['code', 'payment_code']),
            'content' => $this->first($transaction, ['content', 'transaction_content', 'description']),
            'transferType' => $transferType,
            'transferAmount' => $amount,
            'accumulated' => (int) $this->first($transaction, ['accumulated', 'running_balance'], 0),
            'referenceCode' => $this->first($transaction, ['referenceCode', 'reference_code', 'reference_number']),
        ];
    }

    /** @param array<string, mixed> $source
     * @param  array<int, string>  $keys
     */
    private function first(array $source, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $source) && $source[$key] !== null && $source[$key] !== '') {
                return $source[$key];
            }
        }

        return $default;
    }
}
