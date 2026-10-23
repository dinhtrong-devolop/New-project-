<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewaySetting extends Model
{
    protected $hidden = ['partner_key'];

    protected $fillable = [
        'gateway',
        'is_enabled',
        'provider_url',
        'discount_percent',
        'partner_id',
        'partner_key',
        'configured_by',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'discount_percent' => 'decimal:2',
            'partner_key' => 'encrypted',
        ];
    }

    public function configuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configured_by');
    }
}
