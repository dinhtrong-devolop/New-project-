<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WalletAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'not_in:0', 'between:-1000000000,1000000000'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }
}
