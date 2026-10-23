<?php

namespace App\Enums;

enum BankDepositStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Review = 'review';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Đang chờ',
            self::Paid => 'Đã thanh toán',
            self::Review => 'Cần kiểm tra',
            self::Expired => 'Đã hết hạn',
            self::Cancelled => 'Đã hủy',
        };
    }
}
