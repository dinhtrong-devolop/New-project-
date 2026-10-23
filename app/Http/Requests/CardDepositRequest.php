<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(config('services.naptudong.providers', []))],
            'amount' => ['required', 'integer', Rule::in(config('services.naptudong.denominations', []))],
            'serial' => ['required', 'alpha_num:ascii', 'min:8', 'max:32'],
            'code' => ['required', 'alpha_num:ascii', 'min:8', 'max:32'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'provider' => strtoupper(trim((string) $this->input('provider'))),
            'serial' => trim((string) $this->input('serial')),
            'code' => trim((string) $this->input('code')),
        ]);
    }
}
