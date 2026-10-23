<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->withCount('items')
            ->latest()
            ->paginate(12);

        return view('storefront.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorize('view', $order);
        $order->load(['items.product.images', 'items.product.category', 'items.deliveries', 'items.review', 'voucher']);

        return view('storefront.orders.show', compact('order'));
    }
}
