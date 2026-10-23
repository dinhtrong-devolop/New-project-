<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ApplyVoucherRequest;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends ApiController
{
    public function index(Request $request, CartService $carts): JsonResponse
    {
        return $this->cartResponse($request, $carts, 'Cart retrieved successfully');
    }

    public function store(StoreCartItemRequest $request, CartService $carts): JsonResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $carts->add($request, $product, $request->integer('quantity'));

        return $this->cartResponse($request, $carts, 'Product added to cart', 201);
    }

    public function update(UpdateCartItemRequest $request, CartItem $item, CartService $carts): JsonResponse
    {
        $carts->update($request, $item, $request->integer('quantity'));

        return $this->cartResponse($request, $carts, 'Cart item updated');
    }

    public function destroy(Request $request, CartItem $item, CartService $carts): JsonResponse
    {
        $carts->remove($request, $item);

        return $this->cartResponse($request, $carts, 'Cart item removed');
    }

    public function applyVoucher(ApplyVoucherRequest $request, CartService $carts): JsonResponse
    {
        $carts->applyVoucher($request, $request->string('voucher')->value());

        return $this->cartResponse($request, $carts, 'Voucher applied successfully');
    }

    public function removeVoucher(Request $request, CartService $carts): JsonResponse
    {
        $carts->removeVoucher($request);

        return $this->cartResponse($request, $carts, 'Voucher removed');
    }

    private function cartResponse(Request $request, CartService $carts, string $message, int $status = 200): JsonResponse
    {
        $cart = $carts->detailed($carts->resolve($request));

        return $this->success((new CartResource($cart))->resolve($request), $message, $status);
    }
}
