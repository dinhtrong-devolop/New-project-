<?php

namespace App\Enums;

enum InventoryStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Có sẵn',
            self::Reserved => 'Đang giữ chỗ',
            self::Sold => 'Đã bán',
            self::Disabled => 'Đã vô hiệu hóa',
        };
    }
}
