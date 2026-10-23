<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()
            ->with('refundRequest')
            ->withCount('items')
            ->latest()
            ->paginate(min(max($request->integer('per_page', 15), 1), 50));

        return $this->success(
            OrderResource::collection($orders->getCollection())->resolve(),
            'Orders retrieved successfully',
            200,
            ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'total' => $orders->total()]
        );
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $order->load(['items.deliveries', 'voucher', 'refundRequest']);

        return $this->success((new OrderResource($order))->resolve(), 'Order retrieved successfully');
    }
}
