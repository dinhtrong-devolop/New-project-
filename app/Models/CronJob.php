<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronJob extends Model
{
    public const BANK_DEPOSIT_SYNC = 'bank-deposit-sync';

    protected $fillable = [
        'key',
        'name',
        'command',
        'expression',
        'frequency_label',
        'description',
        'is_enabled',
        'last_status',
        'last_run_source',
        'last_message',
        'last_duration_ms',
        'run_count',
        'failure_count',
        'last_scheduler_ping_at',
        'last_started_at',
        'last_finished_at',
        'last_succeeded_at',
        'last_failed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'last_duration_ms' => 'integer',
            'run_count' => 'integer',
            'failure_count' => 'integer',
            'last_scheduler_ping_at' => 'datetime',
            'last_started_at' => 'datetime',
            'last_finished_at' => 'datetime',
            'last_succeeded_at' => 'datetime',
            'last_failed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function schedulerState(): string
    {
        if (! $this->last_scheduler_ping_at) {
            return 'not_installed';
        }

        return $this->last_scheduler_ping_at->greaterThanOrEqualTo(now()->subMinutes(3))
            ? 'healthy'
            : 'stale';
    }

    public static function bankDepositSync(): self
    {
        return self::query()->firstOrCreate(
            ['key' => self::BANK_DEPOSIT_SYNC],
            [
                'name' => 'Nạp tiền Bank',
                'command' => 'shopkey:sync-bank-transactions',
                'expression' => '* * * * *',
                'frequency_label' => 'Mỗi 1 phút',
                'description' => 'Tự động quét và cộng tiền từ giao dịch chuyển khoản ngân hàng.',
                'is_enabled' => true,
            ],
        );
    }
}
