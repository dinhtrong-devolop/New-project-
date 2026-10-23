<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Đơn hàng đã hoàn tất',
            'message' => 'Sản phẩm số của đơn '.$this->order->order_code.' đã sẵn sàng.',
            'order_code' => $this->order->order_code,
            'url' => route('orders.show', $this->order),
        ];
    }
}
