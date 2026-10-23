<?php

namespace App\Enums;

enum CardDepositStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case WrongAmount = 'wrong_amount';
    case Failed = 'failed';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Đang chờ',
            self::Success => 'Thành công',
            self::WrongAmount => 'Sai mệnh giá',
            self::Failed => 'Thất bại',
            self::Maintenance => 'Đang bảo trì',
        };
    }
}
