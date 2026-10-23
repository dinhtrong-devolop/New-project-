<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PaymentGatewaySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'],
            'provider_url' => ['required', 'url:http,https', 'max:2048'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'partner_id' => ['nullable', 'string', 'max:255'],
            'partner_key' => ['nullable', 'string', 'min:8', 'max:4096'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_enabled' => $this->boolean('is_enabled'),
            'provider_url' => rtrim(trim((string) $this->input('provider_url')), '/'),
            'partner_id' => trim((string) $this->input('partner_id')) ?: null,
            'partner_key' => trim((string) $this->input('partner_key')) ?: null,
        ]);
    }
}
