<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['voucher' => ['required', 'string', 'max:50']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['voucher' => strtoupper(trim((string) $this->input('voucher')))]);
    }
}
