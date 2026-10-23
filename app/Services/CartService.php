<?php

namespace App\Services;

use App\Enums\InventoryStatus;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const SESSION_KEY = 'shopkey_cart_session';

    public function __construct(private readonly VoucherService $vouchers) {}

    public function resolve(Request $request, bool $create = true): ?Cart
    {
        if ($request->user()) {
            $cart = $create
                ? $request->user()->cart()->firstOrCreate()
                : $request->user()->cart;

            if ($cart && $request->hasSession()) {
                $this->claimGuestCart($request, $cart);
            }

            return $cart;
        }

        if (! $request->hasSession()) {
            return null;
        }

        $sessionId = $request->session()->get(self::SESSION_KEY);

        if (! $sessionId && $create) {
            $sessionId = (string) Str::uuid();
            $request->session()->put(self::SESSION_KEY, $sessionId);
        }

        if (! $sessionId) {
            return null;
        }

        return $create
            ? Cart::query()->firstOrCreate(['session_id' => $sessionId])
            : Cart::query()->where('session_id', $sessionId)->first();
    }

    public function detailed(?Cart $cart): ?Cart
    {
        return $cart?->load([
            'voucher',
            'items.product' => fn ($query) => $query
                ->with(['category', 'images'])
                ->withCount(['inventories as stock_count' => fn (Builder $inventory) => $inventory
                    ->where('status', InventoryStatus::Available->value)]),
        ]);
    }

    /** @return array{subtotal: int, discount: int, total: int, voucher: ?Voucher} */
    public function totals(?Cart $cart, ?User $user = null): array
    {
        $cart = $this->detailed($cart);
        $subtotal = (int) ($cart?->items->sum(fn (CartItem $item) => $item->product->price * $item->quantity) ?? 0);
        $voucher = $cart?->voucher;
        $discount = 0;

        if ($voucher) {
            try {
                $this->vouchers->findValid($voucher->code, $subtotal, $user);
                $discount = $this->vouchers->discount($voucher, $subtotal);
            } catch (ValidationException) {
                $voucher = null;
            }
        }

        return compact('subtotal', 'discount', 'voucher') + ['total' => $subtotal - $discount];
    }

    public function add(Request $request, Product $product, int $quantity): CartItem
    {
        $this->assertPurchasable($product, $quantity);
        $cart = $this->resolve($request);
        $current = $cart->items()->where('product_id', $product->id)->first();
        $newQuantity = ($current?->quantity ?? 0) + $quantity;

        $this->assertPurchasable($product, $newQuantity);

        return $cart->items()->updateOrCreate(
            ['product_id' => $product->id],
            ['quantity' => $newQuantity]
        );
    }

    public function update(Request $request, CartItem $item, int $quantity): CartItem
    {
        $cart = $this->resolve($request, false);
        abort_unless($cart && $item->cart_id === $cart->id, 404);

        $this->assertPurchasable($item->product, $quantity);
        $item->update(['quantity' => $quantity]);

        return $item->fresh('product');
    }

    public function remove(Request $request, CartItem $item): void
    {
        $cart = $this->resolve($request, false);
        abort_unless($cart && $item->cart_id === $cart->id, 404);
        $item->delete();
    }

    public function applyVoucher(Request $request, string $code): Cart
    {
        $cart = $this->resolve($request);
        $totals = $this->totals($cart, $request->user());
        $voucher = $this->vouchers->findValid($code, $totals['subtotal'], $request->user());
        $cart->update(['voucher_id' => $voucher->id]);

        return $cart->fresh('voucher');
    }

    public function removeVoucher(Request $request): void
    {
        $this->resolve($request, false)?->update(['voucher_id' => null]);
    }

    public function itemCount(Request $request): int
    {
        return (int) ($this->resolve($request, false)?->items()->sum('quantity') ?? 0);
    }

    public function claimGuestCart(Request $request, Cart $userCart): void
    {
        $sessionId = $request->session()->get(self::SESSION_KEY);

        if (! $sessionId) {
            return;
        }

        $guestCart = Cart::query()->where('session_id', $sessionId)->first();

        if (! $guestCart || $guestCart->is($userCart)) {
            $request->session()->forget(self::SESSION_KEY);

            return;
        }

        DB::transaction(function () use ($guestCart, $userCart): void {
            foreach ($guestCart->items()->get() as $guestItem) {
                $product = Product::query()
                    ->whereKey($guestItem->product_id)
                    ->withCount(['inventories as available_stock' => fn (Builder $inventory) => $inventory
                        ->where('status', InventoryStatus::Available->value)])
                    ->first();

                if (! $product
                    || $product->status !== ProductStatus::Active
                    || ! $product->category()->where('is_active', true)->exists()
                    || $product->available_stock < 1) {
                    continue;
                }

                $existing = $userCart->items()->where('product_id', $guestItem->product_id)->first();
                $quantity = min(100, $product->available_stock, ($existing?->quantity ?? 0) + $guestItem->quantity);
                $userCart->items()->updateOrCreate(
                    ['product_id' => $guestItem->product_id],
                    ['quantity' => $quantity]
                );
            }

            if (! $userCart->voucher_id && $guestCart->voucher_id) {
                $userCart->update(['voucher_id' => $guestCart->voucher_id]);
            }

            $guestCart->delete();
        });

        $request->session()->forget(self::SESSION_KEY);
    }

    private function assertPurchasable(Product $product, int $quantity): void
    {
        if ($product->status !== ProductStatus::Active || ! $product->category()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['product' => 'Sản phẩm hiện không thể mua.']);
        }

        $stock = $product->inventories()->where('status', InventoryStatus::Available->value)->count();

        if ($quantity < 1 || $quantity > $stock) {
            throw ValidationException::withMessages(['quantity' => "Kho hiện chỉ còn {$stock} sản phẩm."]);
        }
    }
}
