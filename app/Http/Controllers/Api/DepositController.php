<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CardDepositRequest;
use App\Http\Resources\DepositResource;
use App\Services\CardDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $deposits = $request->user()->cardDeposits()->latest()->paginate(min(max($request->integer('per_page', 15), 1), 50));

        return $this->success(
            DepositResource::collection($deposits->getCollection())->resolve(),
            'Deposits retrieved successfully',
            200,
            ['current_page' => $deposits->currentPage(), 'last_page' => $deposits->lastPage(), 'total' => $deposits->total()]
        );
    }

    public function store(CardDepositRequest $request, CardDepositService $deposits): JsonResponse
    {
        $deposit = $deposits->submit(
            $request->user(),
            $request->string('provider')->value(),
            $request->integer('amount'),
            $request->string('serial')->value(),
            $request->string('code')->value(),
        );

        return $this->success((new DepositResource($deposit))->resolve(), 'Card submitted successfully', 201);
    }
}
