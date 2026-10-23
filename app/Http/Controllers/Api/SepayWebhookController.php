<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Services\BankDepositService;
use App\Services\SepayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SepayWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        BankAccount $bankAccount,
        SepayService $sepay,
        BankDepositService $deposits,
    ): JsonResponse {
        if (! $sepay->verifyWebhook($bankAccount, $request)) {
            return response()->json(['success' => false], 401);
        }

        $payload = $request->validate([
            'id' => ['required'],
            'gateway' => ['required', 'string', 'max:100'],
            'transactionDate' => ['required', 'date'],
            'accountNumber' => ['required', 'string', 'max:100'],
            'subAccount' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:4000'],
            'transferType' => ['required', 'in:in,out'],
            'transferAmount' => ['required', 'integer', 'min:0'],
            'accumulated' => ['nullable', 'integer', 'min:0'],
            'referenceCode' => ['nullable', 'string', 'max:255'],
        ]);

        $deposits->processWebhook($bankAccount, $payload);

        return response()->json(['success' => true]);
    }
}
