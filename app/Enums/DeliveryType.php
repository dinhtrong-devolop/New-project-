<?php

namespace App\Enums;

enum DeliveryType: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Tự động',
            self::Manual => 'Thủ công',
        };
    }
}
