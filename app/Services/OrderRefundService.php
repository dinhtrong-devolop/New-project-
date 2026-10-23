<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\WalletTransactionType;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderRefundService
{
    public function __construct(private readonly WalletService $wallets) {}

    public function refund(Order $order, string $reason): Order
    {
        return DB::transaction(function () use ($order, $reason): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status === PaymentStatus::Refunded) {
                return $locked;
            }

            if ($locked->payment_status !== PaymentStatus::Paid) {
                throw ValidationException::withMessages(['order_status' => 'Chỉ có thể hoàn tiền đơn đã thanh toán.']);
            }

            if ($locked->total > 0) {
                $this->wallets->change(
                    $locked->user,
                    $locked->total,
                    WalletTransactionType::Refund,
                    'refund:'.$locked->order_code,
                    'Hoàn tiền đơn '.$locked->order_code.': '.$reason,
                    $locked,
                );
            }

            $locked->update([
                'payment_status' => PaymentStatus::Refunded,
                'order_status' => OrderStatus::Refunded,
                'refunded_at' => now(),
            ]);

            $locked->refundRequest()->where('status', 'pending')->update([
                'status' => 'approved',
                'admin_note' => $reason,
                'resolved_at' => now(),
            ]);

            return $locked->fresh();
        }, 3);
    }
}
