<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price' => $this->product->price,
            'subtotal' => $this->product->price * $this->quantity,
            'stock' => (int) ($this->product->stock_count ?? 0),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
