<?php

namespace App\Services;

use App\Enums\VoucherType;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    public function findValid(string $code, int $subtotal, ?User $user = null): Voucher
    {
        $voucher = Voucher::query()
            ->where('code', strtoupper(trim($code)))
            ->first();

        if (! $voucher || ! $voucher->is_active) {
            throw ValidationException::withMessages(['voucher' => 'Mã ưu đãi không tồn tại hoặc đã bị tắt.']);
        }

        if ($voucher->starts_at?->isFuture() || $voucher->expires_at?->isPast()) {
            throw ValidationException::withMessages(['voucher' => 'Mã ưu đãi chưa bắt đầu hoặc đã hết hạn.']);
        }

        if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
            throw ValidationException::withMessages(['voucher' => 'Mã ưu đãi đã hết lượt sử dụng.']);
        }

        if ($subtotal < $voucher->minimum_order_amount) {
            $minimum = number_format($voucher->minimum_order_amount, 0, ',', '.');
            throw ValidationException::withMessages(['voucher' => "Đơn hàng cần tối thiểu {$minimum}₫ để dùng mã này."]);
        }

        if ($user && $voucher->usages()->where('user_id', $user->id)->count() >= $voucher->per_user_usage_limit) {
            throw ValidationException::withMessages(['voucher' => 'Bạn đã sử dụng hết lượt của mã ưu đãi này.']);
        }

        return $voucher;
    }

    public function discount(Voucher $voucher, int $subtotal): int
    {
        $discount = $voucher->type === VoucherType::Percentage
            ? (int) floor($subtotal * min($voucher->discount_value, 100) / 100)
            : min($voucher->discount_value, $subtotal);

        if ($voucher->maximum_discount !== null) {
            $discount = min($discount, $voucher->maximum_discount);
        }

        return min($discount, $subtotal);
    }
}
