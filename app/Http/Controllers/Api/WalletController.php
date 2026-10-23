<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WalletTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet()->firstOrCreate([], ['balance' => 0, 'currency' => 'VND']);

        return $this->success([
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
        ], 'Wallet retrieved successfully');
    }

    public function transactions(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet()->firstOrCreate([], ['balance' => 0, 'currency' => 'VND']);
        $transactions = $wallet->transactions()->latest()->paginate(min(max($request->integer('per_page', 15), 1), 50));

        return $this->success(
            WalletTransactionResource::collection($transactions->getCollection())->resolve(),
            'Wallet transactions retrieved successfully',
            200,
            ['current_page' => $transactions->currentPage(), 'last_page' => $transactions->lastPage(), 'total' => $transactions->total()]
        );
    }
}
