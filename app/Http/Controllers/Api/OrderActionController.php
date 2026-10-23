<?php

namespace App\Http\Controllers\Api;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderRefundRequest;
use App\Services\OrderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderActionController extends ApiController
{
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOwner($request, $order);
        $order = DB::transaction(function () use ($order): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->order_status, [OrderStatus::Pending, OrderStatus::Processing], true)
                || $locked->payment_status === PaymentStatus::Paid
                || $locked->items()->where('delivery_status', DeliveryStatus::Delivered->value)->exists()) {
                throw ValidationException::withMessages(['order' => 'Chỉ có thể hủy đơn chưa thanh toán và chưa bàn giao.']);
            }
            $locked->update(['order_status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);

            return $locked->fresh()->load(['items.deliveries', 'voucher']);
        });

        return $this->success((new OrderResource($order))->resolve(), 'Order cancelled successfully');
    }

    public function retryPayment(Request $request, Order $order, OrderPaymentService $payments): JsonResponse
    {
        $this->authorizeOwner($request, $order);
        $order = $payments->retry($order);

        return $this->success((new OrderResource($order))->resolve(), 'Order payment completed successfully');
    }

    public function requestRefund(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOwner($request, $order);
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);

        if ($order->payment_status !== PaymentStatus::Paid || $order->order_status !== OrderStatus::Completed) {
            throw ValidationException::withMessages(['order' => 'Chỉ có thể yêu cầu hoàn tiền cho đơn đã thanh toán và hoàn tất.']);
        }

        $refund = OrderRefundRequest::query()->where('order_id', $order->id)->first();
        if ($refund && $refund->status === 'pending') {
            throw ValidationException::withMessages(['order' => 'Đơn hàng đã có yêu cầu hoàn tiền đang chờ xử lý.']);
        }
        if ($refund && $refund->status === 'approved') {
            throw ValidationException::withMessages(['order' => 'Yêu cầu hoàn tiền của đơn hàng đã được duyệt.']);
        }

        $refund = OrderRefundRequest::query()->updateOrCreate(
            ['order_id' => $order->id],
            ['user_id' => $request->user()->id, 'reason' => trim($data['reason']), 'status' => 'pending', 'admin_note' => null, 'resolved_at' => null],
        );

        return $this->success([
            'id' => $refund->id,
            'order_code' => $order->order_code,
            'reason' => $refund->reason,
            'status' => $refund->status,
            'created_at' => $refund->created_at?->toISOString(),
        ], 'Refund request submitted successfully', 201);
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);
    }
}
