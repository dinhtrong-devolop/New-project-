<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Đang chờ',
            self::Completed => 'Hoàn tất',
            self::Failed => 'Thất bại',
            self::Reversed => 'Đã đảo giao dịch',
        };
    }
}
