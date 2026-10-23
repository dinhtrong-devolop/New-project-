<?php

namespace App\Http\Requests\Admin;

use App\Enums\VoucherType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:vouchers,code'],
            'type' => ['required', Rule::enum(VoucherType::class)],
            'discount_value' => ['required', 'integer', 'min:1', Rule::when($this->input('type') === VoucherType::Percentage->value, ['max:100'])],
            'minimum_order_amount' => ['required', 'integer', 'min:0'],
            'maximum_discount' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_usage_limit' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::upper(trim((string) $this->input('code'))),
            'maximum_discount' => filled($this->input('maximum_discount')) ? $this->input('maximum_discount') : null,
            'usage_limit' => filled($this->input('usage_limit')) ? $this->input('usage_limit') : null,
            'starts_at' => filled($this->input('starts_at')) ? $this->input('starts_at') : null,
            'expires_at' => filled($this->input('expires_at')) ? $this->input('expires_at') : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
