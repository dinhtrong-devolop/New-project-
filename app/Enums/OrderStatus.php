<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Đang chờ',
            self::Processing => 'Đang xử lý',
            self::Completed => 'Hoàn tất',
            self::Cancelled => 'Đã hủy',
            self::Refunded => 'Đã hoàn tiền',
        };
    }
}
