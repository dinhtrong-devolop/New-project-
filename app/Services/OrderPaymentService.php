<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\InventoryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\WalletTransactionType;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\ProductInventory;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderPaymentService
{
    public function __construct(private readonly WalletService $wallets) {}

    public function retry(Order $order): Order
    {
        $completed = DB::transaction(function () use ($order): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->payment_status, [PaymentStatus::Pending, PaymentStatus::Failed], true)
                || ! in_array($locked->order_status, [OrderStatus::Pending, OrderStatus::Processing], true)) {
                throw ValidationException::withMessages(['order' => 'Đơn hàng này không thể thanh toán lại.']);
            }

            $items = $locked->items()->with('product')->lockForUpdate()->get();
            if ($items->isEmpty() || $locked->items()->whereHas('deliveries')->exists()) {
                throw ValidationException::withMessages(['order' => 'Đơn hàng không ở trạng thái có thể bàn giao lại.']);
            }

            $inventoryByItem = [];
            foreach ($items as $item) {
                if (! $item->product || $item->product->status !== ProductStatus::Active || ! $item->product->category()->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages(['order' => "{$item->product_name} hiện không thể mua."]);
                }

                $inventories = ProductInventory::query()->where('product_id', $item->product_id)
                    ->where('status', InventoryStatus::Available->value)->orderBy('id')->lockForUpdate()->limit($item->quantity)->get();
                if ($inventories->count() !== $item->quantity) {
                    throw ValidationException::withMessages(['order' => "{$item->product_name} không còn đủ số lượng trong kho."]);
                }
                $inventoryByItem[$item->id] = $inventories;
            }

            if ($locked->total > 0) {
                $this->wallets->change($locked->user, -$locked->total, WalletTransactionType::Purchase, 'purchase:'.$locked->order_code, 'Thanh toán đơn hàng '.$locked->order_code, $locked);
            }

            foreach ($items as $item) {
                foreach ($inventoryByItem[$item->id] as $inventory) {
                    $inventory->update([
                        'status' => InventoryStatus::Sold,
                        'reservation_token' => null,
                        'reserved_at' => null,
                        'reserved_until' => null,
                        'sold_at' => now(),
                    ]);
                    OrderDelivery::query()->create([
                        'order_item_id' => $item->id,
                        'product_inventory_id' => $inventory->id,
                        'encrypted_payload' => $inventory->encrypted_payload,
                        'delivered_at' => now(),
                    ]);
                }
                $item->update(['delivery_status' => DeliveryStatus::Delivered]);
                $item->product->increment('sold_count', $item->quantity);
            }

            $locked->update([
                'payment_status' => PaymentStatus::Paid,
                'order_status' => OrderStatus::Completed,
                'payment_reference' => 'WALLET-'.Str::upper(Str::random(16)),
                'paid_at' => now(),
                'completed_at' => now(),
            ]);

            return $locked->fresh()->load(['items.deliveries', 'voucher']);
        }, 3);

        try {
            $completed->user->notify(new OrderCompletedNotification($completed));
        } catch (Throwable $exception) {
            Log::warning('Order retry notification failed.', ['order_id' => $completed->id, 'message' => $exception->getMessage()]);
        }

        return $completed;
    }
}
