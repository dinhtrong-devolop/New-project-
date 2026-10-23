<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    protected $fillable = [
        'bank_account_id',
        'bank_deposit_id',
        'provider',
        'provider_transaction_id',
        'gateway',
        'transaction_at',
        'account_number',
        'sub_account',
        'payment_code',
        'transfer_type',
        'amount',
        'accumulated',
        'content',
        'reference_code',
        'provider_payload',
        'matched_at',
    ];

    protected $hidden = ['provider_payload'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'accumulated' => 'integer',
            'provider_payload' => 'array',
            'transaction_at' => 'datetime',
            'matched_at' => 'datetime',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function bankDeposit(): BelongsTo
    {
        return $this->belongsTo(BankDeposit::class);
    }
}
