<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+().\s-]{8,20}$/', Rule::unique('users', 'phone')->ignore($user)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'status' => ['required', Rule::in(['active', 'locked'])],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'confirmed', Password::defaults()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => filled($this->input('phone')) ? trim((string) $this->input('phone')) : null,
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'role.enum' => 'Vai trò người dùng không hợp lệ.',
            'status.in' => 'Trạng thái người dùng không hợp lệ.',
        ];
    }
}
