<?php

namespace App\Http\Resources;

use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $totals = app(CartService::class)->totals($this->resource, $request->user());

        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->items->sum('quantity'),
            'voucher' => $totals['voucher'] ? [
                'code' => $totals['voucher']->code,
                'type' => $totals['voucher']->type->value,
            ] : null,
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'total' => $totals['total'],
        ];
    }
}
