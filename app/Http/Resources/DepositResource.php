<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request_id,
            'provider' => $this->provider,
            'serial_last_four' => $this->serial_last_four,
            'declared_amount' => $this->declared_amount,
            'actual_amount' => $this->actual_amount,
            'received_amount' => $this->received_amount,
            'status' => $this->status->value,
            'message' => $this->provider_message,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
