<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'amount' => ['required', 'integer', 'min:1000', 'max:1000000000'],
        ];
    }
}
