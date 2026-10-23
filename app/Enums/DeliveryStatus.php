<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Đang chờ giao',
            self::Delivered => 'Đã giao',
            self::Failed => 'Giao thất bại',
        };
    }
}
