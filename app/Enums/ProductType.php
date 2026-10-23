<?php

namespace App\Enums;

enum ProductType: string
{
    case LicenseKey = 'license_key';
    case Account = 'account';
    case GiftCard = 'gift_card';
    case Subscription = 'subscription';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LicenseKey => 'Mã bản quyền',
            self::Account => 'Tài khoản',
            self::GiftCard => 'Thẻ quà tặng',
            self::Subscription => 'Gói đăng ký',
            self::Other => 'Khác',
        };
    }
}
