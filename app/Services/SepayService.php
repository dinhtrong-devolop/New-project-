<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankDeposit;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SepayService
{
    public const PROVIDER = 'sepay';

    public function qrUrl(BankDeposit $deposit): string
    {
        $account = $deposit->bankAccount;
        $description = $this->transferDescription($deposit);

        return 'https://vietqr.app/img?'.http_build_query([
            'acc' => $account->account_number,
            'bank' => config('vietnam_bank_bins.'.$account->bank_code, $account->bank_code),
            'amount' => $deposit->amount,
            'des' => $description,
            'template' => 'compact',
            'showInfo' => 'true',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function transferDescription(BankDeposit $deposit): string
    {
        if ($deposit->bankAccount->bank_code === 'VIETINBANK') {
            return 'SEVQR '.$deposit->invoice_code;
        }

        return $deposit->invoice_code;
    }

    public function verifyWebhook(BankAccount $account, Request $request): bool
    {
        $secret = (string) $account->webhook_secret;

        if ($secret === '') {
            return false;
        }

        if ($account->webhook_auth === 'apikey') {
            $authorization = (string) $request->header('Authorization');

            return str_starts_with($authorization, 'Apikey ')
                && hash_equals($secret, substr($authorization, 7));
        }

        $timestamp = (string) $request->header('X-SePay-Timestamp');
        $signature = (string) $request->header('X-SePay-Signature');

        if (! ctype_digit($timestamp) || abs(now()->timestamp - (int) $timestamp) > 300) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    /** @param array<string, scalar|null> $query */
    public function transactions(BankAccount $account, array $query = []): array
    {
        return $this->client($account)
            ->get('https://userapi.sepay.vn/v2/transactions', $query)
            ->throw()
            ->json();
    }

    private function client(BankAccount $account): PendingRequest
    {
        return Http::acceptJson()
            ->withToken((string) $account->api_token)
            ->timeout(15)
            ->retry(2, 250);
    }
}
