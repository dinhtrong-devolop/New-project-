<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'wallet_balance' => $this->whenLoaded('wallet', fn () => $this->wallet?->balance ?? 0),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
