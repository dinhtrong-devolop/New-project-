<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutPageController extends Controller
{
    public function index(Request $request, CartService $carts): View
    {
        $cart = $carts->detailed($carts->resolve($request, false));

        return view('storefront.checkout', [
            'items' => $cart?->items ?? collect(),
            'wallet' => $request->user()?->wallet,
            'totals' => $carts->totals($cart, $request->user()),
        ]);
    }

    public function store(CheckoutRequest $request, CheckoutService $checkout): RedirectResponse
    {
        $order = $checkout->checkout($request->user(), $request->string('customer_note')->trim()->value() ?: null);

        return redirect()->route('orders.show', $order)
            ->with('status', 'Thanh toán thành công. Sản phẩm số đã được giao trong đơn hàng.');
    }
}
