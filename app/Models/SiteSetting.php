<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    public const CACHE_KEY = 'site-settings.general';

    protected $fillable = [
        'key',
        'site_name',
        'site_keywords',
        'site_description',
        'contact_email',
        'chat_label',
        'chat_url',
        'logo_path',
        'dark_logo_path',
        'footer_logo_path',
        'favicon_path',
        'banner_path',
        'cover_path',
        'footer_description',
        'footer_trust_text',
        'footer_columns',
        'footer_copyright',
        'footer_bottom_text',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'footer_columns' => 'array',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'site_name' => 'ShopKey',
            'site_keywords' => 'ShopKey, key bản quyền, phần mềm, gift card, sản phẩm số',
            'site_description' => 'ShopKey - Marketplace sản phẩm số, giao tự động và thanh toán an toàn.',
            'contact_email' => 'support@shopkey.vn',
            'chat_label' => 'Hỗ trợ trực tuyến',
            'chat_url' => null,
            'footer_description' => 'Marketplace sản phẩm số chính hãng với giao hàng tự động, lịch sử bàn giao minh bạch và hỗ trợ rõ ràng.',
            'footer_trust_text' => 'Giao dịch được {site_name} bảo vệ',
            'footer_columns' => [
                [
                    'title' => 'Marketplace',
                    'links' => [
                        ['label' => 'Tất cả sản phẩm', 'url' => '/products'],
                        ['label' => 'Sản phẩm bán chạy', 'url' => '/products?sort=best_selling'],
                        ['label' => 'Sản phẩm mới', 'url' => '/products?sort=latest'],
                        ['label' => 'Giỏ hàng', 'url' => '/cart'],
                    ],
                ],
                [
                    'title' => 'Tài khoản',
                    'links' => [
                        ['label' => 'Tổng quan', 'url' => '/account'],
                        ['label' => 'Đơn hàng', 'url' => '/orders'],
                        ['label' => 'Ví ShopKey', 'url' => '/wallet'],
                        ['label' => 'Nạp tiền', 'url' => '/wallet/deposit/bank'],
                    ],
                ],
                [
                    'title' => 'Hỗ trợ',
                    'links' => [
                        ['label' => 'Liên hệ hỗ trợ', 'url' => 'mailto:support@shopkey.vn'],
                        ['label' => 'Chính sách bảo hành', 'url' => '#'],
                        ['label' => 'Chính sách hoàn tiền', 'url' => '#'],
                        ['label' => 'Điều khoản sử dụng', 'url' => '#'],
                    ],
                ],
            ],
            'footer_copyright' => '© {year} {site_name}. All rights reserved.',
            'footer_bottom_text' => 'Thanh toán an toàn · Giao tự động · Hỗ trợ 24/7',
        ];
    }

    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): self => self::query()->where('key', 'general')->first()
            ?? new self(['key' => 'general'] + self::defaults()));
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assetUrl(string $field): ?string
    {
        $path = $this->getAttribute($field);

        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    /** @return array<int, array{title: string, links: array<int, array{label: string, url: string}>}> */
    public function resolvedFooterColumns(): array
    {
        $columns = $this->footer_columns;

        if (! is_array($columns) || count($columns) === 0) {
            $columns = self::defaults()['footer_columns'];
        }

        return collect($columns)->map(function (array $column): array {
            return [
                'title' => (string) ($column['title'] ?? ''),
                'links' => collect($column['links'] ?? [])
                    ->filter(fn (array $link): bool => filled($link['label'] ?? null)
                        && filled($link['url'] ?? null)
                        && (bool) preg_match('/^(https?:\/\/|\/|#|mailto:|tel:)/i', (string) $link['url']))
                    ->map(fn (array $link): array => [
                        'label' => (string) $link['label'],
                        'url' => (string) $link['url'],
                    ])->values()->all(),
            ];
        })->values()->all();
    }

    public function footerText(string $field): string
    {
        $value = $this->getAttribute($field) ?: self::defaults()[$field] ?? '';

        return str_replace(
            ['{year}', '{site_name}'],
            [(string) now()->year, $this->site_name ?: 'ShopKey'],
            (string) $value,
        );
    }
}
