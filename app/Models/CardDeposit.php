<?php

namespace App\Models;

use App\Enums\CardDepositStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'request_id',
        'provider',
        'serial_hash',
        'serial_last_four',
        'card_code_hash',
        'encrypted_card_code',
        'declared_amount',
        'actual_amount',
        'received_amount',
        'status',
        'provider_transaction_id',
        'provider_message',
        'provider_metadata',
        'submitted_at',
        'processed_at',
    ];

    protected $hidden = [
        'serial_hash',
        'card_code_hash',
        'encrypted_card_code',
        'provider_metadata',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_card_code' => 'encrypted',
            'declared_amount' => 'integer',
            'actual_amount' => 'integer',
            'received_amount' => 'integer',
            'status' => CardDepositStatus::class,
            'provider_metadata' => 'array',
            'submitted_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
