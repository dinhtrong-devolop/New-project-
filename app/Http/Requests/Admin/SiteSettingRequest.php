<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class SiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['logo', 'dark_logo', 'footer_logo', 'favicon', 'banner', 'cover'] as $asset) {
            $this->merge(['remove_'.$asset => $this->boolean('remove_'.$asset)]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'site_keywords' => ['nullable', 'string', 'max:3000'],
            'site_description' => ['nullable', 'string', 'max:2000'],
            'contact_email' => ['required', 'email:rfc', 'max:255'],
            'chat_label' => ['required', 'string', 'max:100'],
            'chat_url' => ['nullable', 'url:http,https', 'max:1000'],
            'footer_description' => ['required', 'string', 'max:1000'],
            'footer_trust_text' => ['required', 'string', 'max:255'],
            'footer_columns' => ['required', 'array', 'size:3'],
            'footer_columns.*.title' => ['required', 'string', 'max:100'],
            'footer_columns.*.links' => ['required', 'array', 'max:6'],
            'footer_columns.*.links.*.label' => ['nullable', 'string', 'max:100'],
            'footer_columns.*.links.*.url' => [
                'nullable',
                'string',
                'max:1000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (filled($value) && ! preg_match('/^(https?:\/\/|\/|#|mailto:|tel:)/i', (string) $value)) {
                        $fail('Liên kết footer chỉ chấp nhận http(s), đường dẫn bắt đầu bằng /, #, mailto: hoặc tel:.');
                    }
                },
            ],
            'footer_copyright' => ['required', 'string', 'max:255'],
            'footer_bottom_text' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', File::image()->types(['png', 'jpg', 'jpeg', 'webp'])->max('4mb')],
            'dark_logo' => ['nullable', File::image()->types(['png', 'jpg', 'jpeg', 'webp'])->max('4mb')],
            'footer_logo' => ['nullable', File::image()->types(['png', 'jpg', 'jpeg', 'webp'])->max('4mb')],
            'favicon' => ['nullable', File::types(['png', 'jpg', 'jpeg', 'webp', 'ico'])->max('2mb')],
            'banner' => ['nullable', File::image()->types(['png', 'jpg', 'jpeg', 'webp'])->max('8mb')],
            'cover' => ['nullable', File::image()->types(['png', 'jpg', 'jpeg', 'webp'])->max('8mb')],
            'remove_logo' => ['boolean'],
            'remove_dark_logo' => ['boolean'],
            'remove_footer_logo' => ['boolean'],
            'remove_favicon' => ['boolean'],
            'remove_banner' => ['boolean'],
            'remove_cover' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'site_name.required' => 'Tên trang web là bắt buộc.',
            'contact_email.required' => 'Email liên hệ là bắt buộc.',
            'contact_email.email' => 'Email liên hệ không đúng định dạng.',
            'chat_url.url' => 'Đường dẫn chat phải bắt đầu bằng http:// hoặc https://.',
            'footer_columns.size' => 'Footer cần có đúng 3 cột liên kết.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                foreach ($this->input('footer_columns', []) as $columnIndex => $column) {
                    foreach ($column['links'] ?? [] as $linkIndex => $link) {
                        $hasLabel = filled($link['label'] ?? null);
                        $hasUrl = filled($link['url'] ?? null);

                        if ($hasLabel !== $hasUrl) {
                            $validator->errors()->add(
                                "footer_columns.{$columnIndex}.links.{$linkIndex}",
                                'Mỗi liên kết footer phải có đủ tên hiển thị và đường dẫn.',
                            );
                        }
                    }
                }
            },
        ];
    }
}
