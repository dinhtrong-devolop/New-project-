<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'webhook_key',
        'provider',
        'bank_code',
        'bank_name',
        'account_number',
        'account_holder',
        'branch',
        'deposit_prefix',
        'minimum_deposit',
        'maximum_deposit',
        'is_visible',
        'auto_confirm',
        'webhook_auth',
        'api_token',
        'webhook_secret',
        'notes',
        'configured_by',
    ];

    protected $hidden = ['api_token', 'webhook_secret'];

    protected function casts(): array
    {
        return [
            'minimum_deposit' => 'integer',
            'maximum_deposit' => 'integer',
            'is_visible' => 'boolean',
            'auto_confirm' => 'boolean',
            'api_token' => 'encrypted',
            'webhook_secret' => 'encrypted',
        ];
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(BankDeposit::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function configuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'configured_by');
    }
}
