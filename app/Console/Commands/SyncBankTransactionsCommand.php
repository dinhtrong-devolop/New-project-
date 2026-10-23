<?php

namespace App\Console\Commands;

use App\Models\CronJob;
use App\Services\BankTransactionSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SyncBankTransactionsCommand extends Command
{
    protected $signature = 'shopkey:sync-bank-transactions {--source=scheduler : scheduler hoặc manual}';

    protected $description = 'Quét giao dịch SePay và tự động đối soát hóa đơn nạp tiền ngân hàng';

    public function handle(BankTransactionSyncService $sync): int
    {
        $source = $this->option('source') === 'manual' ? 'manual' : 'scheduler';
        $job = CronJob::bankDepositSync();

        if ($source === 'scheduler') {
            $job->forceFill(['last_scheduler_ping_at' => now()])->save();
        }

        if (! $job->is_enabled && $source === 'scheduler') {
            $this->components->info('Tác vụ đang tắt; scheduler heartbeat đã được ghi nhận.');

            return self::SUCCESS;
        }

        $lock = Cache::lock('cron-job:'.$job->key, 55);

        if (! $lock->get()) {
            $this->components->warn('Tác vụ đang được tiến trình khác xử lý.');

            return self::SUCCESS;
        }

        $startedAt = now();
        $timer = hrtime(true);
        $job->forceFill([
            'last_status' => 'running',
            'last_run_source' => $source,
            'last_message' => 'Đang quét giao dịch ngân hàng...',
            'last_started_at' => $startedAt,
            'last_finished_at' => null,
        ])->save();

        try {
            $summary = $sync->sync();
            $message = "Đã quét {$summary['accounts']} tài khoản, nhận {$summary['received']} giao dịch, ghi mới {$summary['created']} và khớp {$summary['matched']} hóa đơn.";

            $job->forceFill([
                'last_status' => 'success',
                'last_message' => $message,
                'last_duration_ms' => $this->duration($timer),
                'last_finished_at' => now(),
                'last_succeeded_at' => now(),
                'run_count' => $job->run_count + 1,
            ])->save();

            $this->components->info($message);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $message = Str::limit($exception->getMessage(), 2000);

            $job->forceFill([
                'last_status' => 'failed',
                'last_message' => $message,
                'last_duration_ms' => $this->duration($timer),
                'last_finished_at' => now(),
                'last_failed_at' => now(),
                'run_count' => $job->run_count + 1,
                'failure_count' => $job->failure_count + 1,
            ])->save();

            $this->components->error($message);

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }

    private function duration(int $timer): int
    {
        return (int) round((hrtime(true) - $timer) / 1_000_000);
    }
}
