<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Quản trị viên',
            self::Staff => 'Nhân viên',
            self::Customer => 'Người dùng',
        };
    }
}
