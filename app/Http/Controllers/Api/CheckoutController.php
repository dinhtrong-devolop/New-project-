<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends ApiController
{
    public function __invoke(CheckoutRequest $request, CheckoutService $checkout): JsonResponse
    {
        $order = $checkout->checkout($request->user(), $request->string('customer_note')->trim()->value() ?: null);

        return $this->success((new OrderResource($order))->resolve(), 'Checkout completed successfully', 201);
    }
}
