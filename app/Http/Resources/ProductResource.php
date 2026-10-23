<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'product_type' => $this->product_type->value,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'discount_percent' => $this->original_price && $this->original_price > $this->price
                ? (int) round((1 - ($this->price / $this->original_price)) * 100)
                : 0,
            'delivery_type' => $this->delivery_type->value,
            'warranty_days' => $this->warranty_days,
            'sold_count' => $this->sold_count,
            'stock' => (int) ($this->stock_count ?? 0),
            'rating_average' => (float) $this->rating_average,
            'rating_count' => $this->rating_count,
            'is_featured' => $this->is_featured,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => asset('storage/'.$image->path),
                'alt' => $image->alt_text,
                'primary' => $image->is_primary,
            ])),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
