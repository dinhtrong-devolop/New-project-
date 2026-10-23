<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyVoucherRequest;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartPageController extends Controller
{
    public function index(Request $request, CartService $carts): View
    {
        $cart = $carts->detailed($carts->resolve($request, false));

        return view('storefront.cart', [
            'items' => $cart?->items ?? collect(),
            'cart' => $cart,
            'totals' => $carts->totals($cart, $request->user()),
        ]);
    }

    public function store(StoreCartItemRequest $request, CartService $carts): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $carts->add($request, $product, $request->integer('quantity'));

        if ($request->string('action')->value() === 'buy_now') {
            return redirect()->route('checkout.index');
        }

        return back()->with('status', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function update(UpdateCartItemRequest $request, CartItem $item, CartService $carts): RedirectResponse
    {
        $carts->update($request, $item, $request->integer('quantity'));

        return back()->with('status', 'Đã cập nhật số lượng.');
    }

    public function destroy(Request $request, CartItem $item, CartService $carts): RedirectResponse
    {
        $carts->remove($request, $item);

        return back()->with('status', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function applyVoucher(ApplyVoucherRequest $request, CartService $carts): RedirectResponse
    {
        $carts->applyVoucher($request, $request->string('voucher')->value());

        return back()->with('status', 'Đã áp dụng mã ưu đãi.');
    }

    public function removeVoucher(Request $request, CartService $carts): RedirectResponse
    {
        $carts->removeVoucher($request);

        return back()->with('status', 'Đã bỏ mã ưu đãi.');
    }
}
