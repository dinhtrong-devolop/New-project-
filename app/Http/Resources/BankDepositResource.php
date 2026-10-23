<?php

namespace App\Http\Resources;

use App\Services\SepayService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankDepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sepay = app(SepayService::class);

        return [
            'id' => $this->id,
            'invoice_code' => $this->invoice_code,
            'amount' => $this->amount,
            'received_amount' => $this->received_amount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'bank_account' => new BankAccountResource($this->whenLoaded('bankAccount')),
            'transfer_content' => $this->relationLoaded('bankAccount') ? $sepay->transferDescription($this->resource) : null,
            'qr_url' => $this->relationLoaded('bankAccount') ? $sepay->qrUrl($this->resource) : null,
            'expires_at' => $this->expires_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
