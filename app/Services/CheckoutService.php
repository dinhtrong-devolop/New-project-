<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\InventoryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\WalletTransactionType;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutService
{
    public function __construct(
        private readonly VoucherService $vouchers,
        private readonly WalletService $wallets,
    ) {}

    public function checkout(User $user, ?string $note = null): Order
    {
        try {
            $order = DB::transaction(function () use ($user, $note): Order {
                $cart = Cart::query()->where('user_id', $user->id)->lockForUpdate()->first();
                $items = $cart?->items()->with('product')->lockForUpdate()->get();

                if (! $cart || ! $items || $items->isEmpty()) {
                    throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
                }

                $subtotal = 0;
                $inventoryByProduct = [];

                foreach ($items as $item) {
                    $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->firstOrFail();

                    if ($product->status !== ProductStatus::Active || ! $product->category()->where('is_active', true)->exists()) {
                        throw ValidationException::withMessages(['cart' => "{$product->name} hiện không thể mua."]);
                    }

                    $inventories = ProductInventory::query()
                        ->where('product_id', $product->id)
                        ->where('status', InventoryStatus::Available->value)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->limit($item->quantity)
                        ->get();

                    if ($inventories->count() !== $item->quantity) {
                        throw ValidationException::withMessages(['cart' => "{$product->name} không còn đủ số lượng trong kho."]);
                    }

                    $item->setRelation('product', $product);
                    $inventoryByProduct[$product->id] = $inventories;
                    $subtotal += $product->price * $item->quantity;
                }

                $voucher = $this->lockedVoucher($cart, $subtotal, $user);
                $discount = $voucher ? $this->vouchers->discount($voucher, $subtotal) : 0;
                $total = $subtotal - $discount;

                $order = Order::query()->create([
                    'order_code' => $this->uniqueOrderCode(),
                    'user_id' => $user->id,
                    'voucher_id' => $voucher?->id,
                    'voucher_code' => $voucher?->code,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'total' => $total,
                    'payment_method' => 'wallet',
                    'payment_status' => PaymentStatus::Paid,
                    'order_status' => OrderStatus::Completed,
                    'payment_reference' => 'WALLET-'.Str::upper(Str::random(16)),
                    'customer_note' => $note,
                    'paid_at' => now(),
                    'completed_at' => now(),
                ]);

                if ($total > 0) {
                    $this->wallets->change(
                        $user,
                        -$total,
                        WalletTransactionType::Purchase,
                        'purchase:'.$order->order_code,
                        'Thanh toán đơn hàng '.$order->order_code,
                        $order,
                    );
                }

                foreach ($items as $cartItem) {
                    $product = $cartItem->product;
                    $orderItem = OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'product_type' => $product->product_type,
                        'unit_price' => $product->price,
                        'quantity' => $cartItem->quantity,
                        'subtotal' => $product->price * $cartItem->quantity,
                        'warranty_days' => $product->warranty_days,
                        'delivery_status' => DeliveryStatus::Delivered,
                    ]);

                    foreach ($inventoryByProduct[$product->id] as $inventory) {
                        $inventory->update([
                            'status' => InventoryStatus::Sold,
                            'reservation_token' => null,
                            'reserved_at' => null,
                            'reserved_until' => null,
                            'sold_at' => now(),
                        ]);

                        OrderDelivery::query()->create([
                            'order_item_id' => $orderItem->id,
                            'product_inventory_id' => $inventory->id,
                            'encrypted_payload' => $inventory->encrypted_payload,
                            'delivered_at' => now(),
                        ]);
                    }

                    $product->increment('sold_count', $cartItem->quantity);
                }

                if ($voucher) {
                    VoucherUsage::query()->create([
                        'voucher_id' => $voucher->id,
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'discount_amount' => $discount,
                        'used_at' => now(),
                    ]);
                    $voucher->increment('used_count');
                }

                $cart->items()->delete();
                $cart->update(['voucher_id' => null]);

                return $order->load(['items.deliveries', 'voucher']);
            }, 3);

            try {
                $user->notify(new OrderCompletedNotification($order));
            } catch (Throwable $notificationError) {
                Log::warning('Order notification failed.', ['order_id' => $order->id, 'message' => $notificationError->getMessage()]);
            }

            return $order;
        } catch (Throwable $exception) {
            Log::warning('Checkout failed.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function lockedVoucher(Cart $cart, int $subtotal, User $user): ?Voucher
    {
        if (! $cart->voucher_id) {
            return null;
        }

        $voucher = Voucher::query()->whereKey($cart->voucher_id)->lockForUpdate()->first();

        if (! $voucher) {
            throw ValidationException::withMessages(['voucher' => 'Mã ưu đãi không còn tồn tại.']);
        }

        return $this->vouchers->findValid($voucher->code, $subtotal, $user);
    }

    private function uniqueOrderCode(): string
    {
        do {
            $code = 'SK'.now()->format('ymd').Str::upper(Str::random(10));
        } while (Order::query()->where('order_code', $code)->exists());

        return $code;
    }
}
