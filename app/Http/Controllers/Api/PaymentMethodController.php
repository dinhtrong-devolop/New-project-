<?php

namespace App\Http\Controllers\Api;

use App\Models\BankAccount;
use App\Models\PaymentGatewaySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $cardEnabled = PaymentGatewaySetting::query()->where('gateway', 'naptudong')->where('is_enabled', true)->exists();
        $bankEnabled = BankAccount::query()->where('is_visible', true)->exists();

        return $this->success([
            'checkout' => [
                ['code' => 'wallet', 'name' => 'Ví ShopKey', 'enabled' => true, 'balance' => (int) ($request->user()->wallet?->balance ?? 0)],
            ],
            'top_up' => [
                ['code' => 'bank_transfer', 'name' => 'Chuyển khoản ngân hàng', 'enabled' => $bankEnabled],
                ['code' => 'card', 'name' => 'Nạp thẻ cào', 'enabled' => $cardEnabled],
            ],
        ], 'Payment methods retrieved successfully');
    }
}
