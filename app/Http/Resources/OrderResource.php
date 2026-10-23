<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'total' => $this->total,
            'voucher_code' => $this->voucher_code,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status->value,
            'order_status' => $this->order_status->value,
            'customer_note' => $this->customer_note,
            'items_count' => $this->whenCounted('items'),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'refund_request' => $this->whenLoaded('refundRequest', fn () => $this->refundRequest ? [
                'id' => $this->refundRequest->id,
                'reason' => $this->refundRequest->reason,
                'status' => $this->refundRequest->status,
                'admin_note' => $this->refundRequest->admin_note,
                'resolved_at' => $this->refundRequest->resolved_at?->toISOString(),
                'created_at' => $this->refundRequest->created_at?->toISOString(),
            ] : null),
            'paid_at' => $this->paid_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
