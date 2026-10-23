<?php

namespace App\Models;

use App\Enums\BankDepositStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BankDeposit extends Model
{
    protected $fillable = [
        'user_id',
        'bank_account_id',
        'invoice_code',
        'amount',
        'received_amount',
        'status',
        'sepay_transaction_id',
        'reference_code',
        'transfer_content',
        'provider_payload',
        'approved_by',
        'admin_note',
        'expires_at',
        'paid_at',
    ];

    protected $hidden = ['provider_payload'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'received_amount' => 'integer',
            'status' => BankDepositStatus::class,
            'provider_payload' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(BankTransaction::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
