<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\OrderRefundService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with('user')
            ->withCount('items')
            ->when($request->string('search')->trim()->value(), fn (Builder $query, string $search) => $query
                ->where(fn (Builder $inner) => $inner->where('order_code', 'like', "%{$search}%")->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('email', 'like', "%{$search}%"))))
            ->when($request->string('status')->value(), fn (Builder $query, string $status) => $query->where('order_status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'items.product', 'items.deliveries.inventory', 'refundRequest']);

        return view('admin.orders.show', compact('order'));
    }

    public function update(OrderStatusRequest $request, Order $order, ActivityLogger $logger, OrderRefundService $refunds): RedirectResponse
    {
        $data = $request->safe()->only(['order_status', 'payment_status']);
        $orderStatus = $data['order_status'];
        $paymentStatus = $data['payment_status'];

        if ($paymentStatus !== $order->payment_status->value) {
            throw ValidationException::withMessages([
                'payment_status' => 'Không thể đổi trạng thái thanh toán thủ công. Hãy dùng quy trình checkout hoặc hoàn tiền.',
            ]);
        }

        if (in_array($order->order_status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)
            && $orderStatus !== $order->order_status->value) {
            throw ValidationException::withMessages([
                'order_status' => 'Không thể mở lại đơn hàng đã hủy hoặc đã hoàn tiền.',
            ]);
        }

        if (in_array($orderStatus, [OrderStatus::Pending->value, OrderStatus::Processing->value], true)
            && $order->items()->where('delivery_status', DeliveryStatus::Delivered->value)->exists()) {
            throw ValidationException::withMessages([
                'order_status' => 'Không thể chuyển đơn đã bàn giao về trạng thái chờ xử lý.',
            ]);
        }

        if ($orderStatus === OrderStatus::Refunded->value || ($orderStatus === OrderStatus::Cancelled->value && $order->payment_status === PaymentStatus::Paid)) {
            $order = $refunds->refund($order, $request->string('admin_note')->trim()->value() ?: 'Quản trị viên xử lý hoàn tiền');
            $logger->log($request, 'admin.order.refunded', $order, [
                'amount' => $order->total,
                'note' => $request->input('admin_note'),
            ]);

            return back()->with('status', 'Đã hoàn tiền vào ví khách hàng và ghi bút toán đối soát.');
        }

        if ($orderStatus === OrderStatus::Completed->value && ! $order->completed_at) {
            $data['completed_at'] = now();
        }

        if ($orderStatus === OrderStatus::Cancelled->value && ! $order->cancelled_at) {
            $data['cancelled_at'] = now();
        }

        $order->update($data);
        $logger->log($request, 'admin.order.status_updated', $order, [
            'order_status' => $orderStatus,
            'payment_status' => $paymentStatus,
            'note' => $request->input('admin_note'),
        ]);

        return back()->with('status', 'Đã cập nhật trạng thái đơn hàng.');
    }

    public function refundRequest(Request $request, Order $order, ActivityLogger $logger, OrderRefundService $refunds): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $refundRequest = $order->refundRequest()->where('status', 'pending')->firstOrFail();
        $note = trim((string) ($data['admin_note'] ?? ''));

        if ($data['action'] === 'approve') {
            $refunds->refund($order, $note ?: 'Quản trị viên duyệt yêu cầu hoàn tiền');
            $logger->log($request, 'admin.order.refund_request_approved', $order, ['request_id' => $refundRequest->id, 'note' => $note]);

            return back()->with('status', 'Đã duyệt yêu cầu và hoàn tiền vào ví khách hàng.');
        }

        $refundRequest->update(['status' => 'rejected', 'admin_note' => $note ?: 'Yêu cầu không đủ điều kiện hoàn tiền', 'resolved_at' => now()]);
        $logger->log($request, 'admin.order.refund_request_rejected', $order, ['request_id' => $refundRequest->id, 'note' => $note]);

        return back()->with('status', 'Đã từ chối yêu cầu hoàn tiền.');
    }
}
