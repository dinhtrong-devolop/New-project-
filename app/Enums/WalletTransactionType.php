<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Deposit = 'deposit';
    case Purchase = 'purchase';
    case Refund = 'refund';
    case AdminAdjustment = 'admin_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Nạp tiền',
            self::Purchase => 'Thanh toán',
            self::Refund => 'Hoàn tiền',
            self::AdminAdjustment => 'Quản trị viên điều chỉnh',
        };
    }
}
