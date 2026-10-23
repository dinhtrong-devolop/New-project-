<?php

namespace App\Models;

use App\Enums\InventoryStatus;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'inventory_type',
        'encrypted_payload',
        'payload_fingerprint',
        'display_hint',
        'status',
        'reservation_token',
        'reserved_at',
        'reserved_until',
        'sold_at',
        'disabled_at',
    ];

    protected $hidden = [
        'encrypted_payload',
        'payload_fingerprint',
        'reservation_token',
    ];

    protected function casts(): array
    {
        return [
            'inventory_type' => ProductType::class,
            'encrypted_payload' => 'encrypted:array',
            'status' => InventoryStatus::class,
            'reserved_at' => 'datetime',
            'reserved_until' => 'datetime',
            'sold_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(OrderDelivery::class);
    }
}
