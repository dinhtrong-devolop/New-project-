<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'product_type',
        'unit_price',
        'quantity',
        'subtotal',
        'warranty_days',
        'delivery_status',
    ];

    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'subtotal' => 'integer',
            'warranty_days' => 'integer',
            'delivery_status' => DeliveryStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(OrderDelivery::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
