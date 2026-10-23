<?php

namespace Database\Factories;

use App\Enums\InventoryStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProductInventory> */
class ProductInventoryFactory extends Factory
{
    public function definition(): array
    {
        $value = implode('-', [
            Str::upper(Str::random(5)),
            Str::upper(Str::random(5)),
            Str::upper(Str::random(5)),
            Str::upper(Str::random(5)),
            Str::upper(Str::random(5)),
        ]);

        return [
            'product_id' => Product::factory(),
            'inventory_type' => ProductType::LicenseKey,
            'encrypted_payload' => ['key' => $value],
            'payload_fingerprint' => hash('sha256', Str::lower($value)),
            'display_hint' => '•••••-'.substr($value, -5),
            'status' => InventoryStatus::Available,
        ];
    }
}
