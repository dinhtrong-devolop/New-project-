<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'bank_code' => strtoupper(trim((string) $this->input('bank_code'))),
            'account_number' => preg_replace('/\s+/', '', trim((string) $this->input('account_number'))),
            'account_holder' => trim((string) $this->input('account_holder')),
            'deposit_prefix' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('deposit_prefix'))),
            'is_visible' => $this->boolean('is_visible'),
            'auto_confirm' => $this->boolean('auto_confirm'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bank_code' => ['required', Rule::in(array_keys(config('vietnam_banks', [])))],
            'account_number' => [
                'required',
                'string',
                'regex:/^[A-Za-z0-9]{4,100}$/',
                Rule::unique('bank_accounts', 'account_number')->ignore($this->route('bank_account')),
            ],
            'account_holder' => ['required', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'deposit_prefix' => ['required', 'string', 'regex:/^[A-Z0-9]{2,12}$/'],
            'minimum_deposit' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'maximum_deposit' => ['nullable', 'integer', 'gte:minimum_deposit', 'max:1000000000'],
            'is_visible' => ['required', 'boolean'],
            'auto_confirm' => ['required', 'boolean'],
            'provider' => ['required', Rule::in(['sepay'])],
            'webhook_auth' => ['required', Rule::in(['hmac', 'apikey'])],
            'api_token' => ['nullable', 'string', 'max:4096'],
            'webhook_secret' => ['nullable', 'string', 'min:12', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'bank_code.required' => 'Vui lòng chọn ngân hàng.',
            'account_number.regex' => 'Số tài khoản chỉ được chứa chữ và số.',
            'maximum_deposit.gte' => 'Số tiền nạp tối đa phải lớn hơn hoặc bằng số tiền tối thiểu.',
            'webhook_secret.min' => 'Khóa xác thực webhook phải có ít nhất 12 ký tự.',
        ];
    }
}
