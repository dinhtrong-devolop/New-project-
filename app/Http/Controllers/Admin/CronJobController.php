<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CronJob;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CronJobController extends Controller
{
    public function index(): View
    {
        CronJob::bankDepositSync();
        $jobs = CronJob::query()->orderBy('id')->get();

        return view('admin.cron-jobs.index', [
            'jobs' => $jobs,
            'schedulerCommand' => '* * * * * cd '.base_path().' && '.PHP_BINARY.' artisan schedule:run >> /dev/null 2>&1',
            'workerCommand' => PHP_BINARY.' artisan schedule:work',
        ]);
    }

    public function toggle(Request $request, CronJob $cronJob, ActivityLogger $logger): RedirectResponse
    {
        $cronJob->update(['is_enabled' => ! $cronJob->is_enabled]);
        $logger->log($request, 'admin.cron_job.toggled', $cronJob, ['is_enabled' => $cronJob->is_enabled]);

        return back()->with('status', $cronJob->is_enabled ? 'Đã bật tác vụ tự động.' : 'Đã tắt tác vụ tự động.');
    }

    public function run(Request $request, CronJob $cronJob, ActivityLogger $logger): RedirectResponse
    {
        $exitCode = Artisan::call($cronJob->command, ['--source' => 'manual']);
        $cronJob->refresh();

        $logger->log($request, 'admin.cron_job.ran_manually', $cronJob, [
            'exit_code' => $exitCode,
            'status' => $cronJob->last_status,
        ]);

        if ($exitCode !== 0) {
            return back()->withErrors(['cron' => 'Tác vụ chạy thất bại: '.($cronJob->last_message ?: 'Không có thông báo chi tiết.')]);
        }

        return back()->with('status', 'Đã chạy thử tác vụ: '.($cronJob->last_message ?: $cronJob->name));
    }
}
