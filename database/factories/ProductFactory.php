<?php

namespace Database\Factories;

use App\Enums\DeliveryType;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->numberBetween(5, 300) * 10_000;

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'sku' => 'SK-'.Str::upper(Str::random(10)),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'product_type' => fake()->randomElement(ProductType::cases()),
            'price' => $price,
            'original_price' => fake()->boolean(40) ? $price + fake()->numberBetween(1, 20) * 10_000 : null,
            'status' => ProductStatus::Active,
            'delivery_type' => DeliveryType::Automatic,
            'warranty_days' => fake()->randomElement([0, 7, 30, 90, 365]),
            'published_at' => now(),
        ];
    }
}
