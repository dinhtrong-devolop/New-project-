<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    /** @return array<int, self> */
    public static function manageableCases(): array
    {
        return [self::Draft, self::Active, self::Inactive];
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Bản nháp',
            self::Active => 'Đang bán',
            self::Inactive => 'Ngừng bán',
            self::Archived => 'Đã lưu trữ',
        };
    }
}
