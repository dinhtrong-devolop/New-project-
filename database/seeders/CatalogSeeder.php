<?php

namespace Database\Seeders;

use App\Enums\DeliveryType;
use App\Enums\InventoryStatus;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Windows Keys', 'slug' => 'windows-keys', 'description' => 'Bản quyền hệ điều hành rõ nguồn gốc.'],
            ['name' => 'Office Keys', 'slug' => 'office-keys', 'description' => 'Công cụ văn phòng cho học tập và doanh nghiệp.'],
            ['name' => 'Game Keys', 'slug' => 'game-keys', 'description' => 'Mã game chính hãng, kích hoạt nhanh.'],
            ['name' => 'Gift Cards', 'slug' => 'gift-cards', 'description' => 'Thẻ quà tặng cho các nền tảng phổ biến.'],
            ['name' => 'Software', 'slug' => 'software', 'description' => 'Phần mềm bảo mật và tiện ích bản quyền.'],
            ['name' => 'Subscriptions', 'slug' => 'subscriptions', 'description' => 'Gói thuê bao số được phép phân phối.'],
            ['name' => 'Digital Accounts', 'slug' => 'digital-accounts', 'description' => 'Tài khoản sở hữu hợp pháp, đủ điều kiện chuyển giao.'],
            ['name' => 'Other Digital', 'slug' => 'other-digital', 'description' => 'Những sản phẩm số hữu ích khác.'],
        ])->mapWithKeys(function (array $category, int $index): array {
            $model = Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true, 'sort_order' => ($index + 1) * 10]
            );

            return [$category['slug'] => $model];
        });

        $products = [
            ['windows-keys', 'Windows 11 Pro License', 'windows-11-pro-license', 'SK-WIN11-PRO', 'license_key', 249000, 399000, 328, 4.9, true, 365],
            ['office-keys', 'Office Home & Business 2024', 'office-home-business-2024', 'SK-OFFICE-24', 'license_key', 489000, 699000, 214, 4.8, true, 365],
            ['game-keys', 'Nebula Quest PC Game Key', 'nebula-quest-pc-game-key', 'SK-GAME-NQ', 'license_key', 179000, 259000, 189, 4.7, true, 30],
            ['gift-cards', 'Gaming Wallet Gift Card 500K', 'gaming-wallet-gift-card-500k', 'SK-GIFT-500', 'gift_card', 495000, 520000, 612, 5.0, true, 0],
            ['software', 'Shield Antivirus Total Security', 'shield-antivirus-total-security', 'SK-AV-TS', 'license_key', 199000, 349000, 146, 4.8, true, 365],
            ['subscriptions', 'Cloud Storage Plus 1TB — 1 Year', 'cloud-storage-plus-1tb', 'SK-CLOUD-1TB', 'subscription', 329000, 459000, 281, 4.9, true, 30],
            ['subscriptions', 'Creative Design Suite — 3 Months', 'creative-design-suite-3-months', 'SK-DESIGN-3M', 'subscription', 149000, 219000, 97, 4.6, false, 30],
            ['digital-accounts', 'Learning Platform Premium Account', 'learning-platform-premium-account', 'SK-LEARN-PRO', 'account', 119000, 179000, 84, 4.7, false, 14],
            ['software', 'Password Vault Premium — 1 Year', 'password-vault-premium-1-year', 'SK-VAULT-1Y', 'subscription', 159000, 249000, 132, 4.8, false, 365],
            ['game-keys', 'Pixel Racers Deluxe PC Key', 'pixel-racers-deluxe-pc-key', 'SK-GAME-PRD', 'license_key', 99000, 159000, 204, 4.6, false, 30],
            ['gift-cards', 'App Marketplace Gift Card 200K', 'app-marketplace-gift-card-200k', 'SK-GIFT-200', 'gift_card', 198000, null, 445, 4.9, false, 0],
            ['other-digital', 'Remote Work Productivity Pack', 'remote-work-productivity-pack', 'SK-WORK-PACK', 'other', 79000, 129000, 61, 4.5, false, 7],
        ];

        foreach ($products as $index => [$categorySlug, $name, $slug, $sku, $type, $price, $originalPrice, $sold, $rating, $featured, $warranty]) {
            $product = Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $categories[$categorySlug]->id,
                    'name' => $name,
                    'slug' => $slug,
                    'short_description' => 'Sản phẩm số chính hãng, giao tự động và có lịch sử bàn giao rõ ràng.',
                    'description' => "{$name} được ShopKey phân phối qua nguồn hợp lệ. Sau khi thanh toán thành công, thông tin kích hoạt được bàn giao ngay trong chi tiết đơn hàng. Sản phẩm có hướng dẫn sử dụng, chính sách bảo hành minh bạch và đội ngũ hỗ trợ khi cần.",
                    'product_type' => $type,
                    'price' => $price,
                    'original_price' => $originalPrice,
                    'status' => ProductStatus::Active,
                    'delivery_type' => DeliveryType::Automatic,
                    'warranty_days' => $warranty,
                    'sold_count' => $sold,
                    'rating_average' => $rating,
                    'rating_count' => max(12, (int) floor($sold / 3)),
                    'is_featured' => $featured,
                    'published_at' => now()->subDays($index),
                ]
            );

            foreach (range(1, 12) as $stockIndex) {
                $inventoryValue = sprintf('SHOPKEY-DEMO-%s-%03d', $sku, $stockIndex);
                $fingerprint = hash('sha256', strtolower($inventoryValue));

                ProductInventory::query()->firstOrCreate(
                    ['product_id' => $product->id, 'payload_fingerprint' => $fingerprint],
                    [
                        'inventory_type' => $type,
                        'encrypted_payload' => ['key' => $inventoryValue, 'demo' => true],
                        'display_hint' => '••••••••-'.substr($inventoryValue, -3),
                        'status' => InventoryStatus::Available,
                    ]
                );
            }
        }
    }
}
