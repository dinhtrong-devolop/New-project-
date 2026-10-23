<?php

namespace App\Enums;

enum VoucherType: string
{
    case Percentage = 'percentage';
    case FixedAmount = 'fixed_amount';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Theo phần trăm',
            self::FixedAmount => 'Số tiền cố định',
        };
    }
}
