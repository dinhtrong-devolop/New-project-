<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'product_inventory_id',
        'encrypted_payload',
        'delivered_at',
    ];

    protected $hidden = ['encrypted_payload'];

    protected function casts(): array
    {
        return [
            'encrypted_payload' => 'encrypted:array',
            'delivered_at' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(ProductInventory::class, 'product_inventory_id');
    }
}
