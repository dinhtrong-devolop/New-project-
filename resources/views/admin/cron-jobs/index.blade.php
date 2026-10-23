@extends('layouts.admin')

@section('title', 'Quản lý Cron Jobs')
@section('page_title', 'Quản lý Cron Jobs')

@section('content')
    @php
        $healthyCount = $jobs->filter(fn ($job) => $job->schedulerState() === 'healthy')->count();
        $failedCount = $jobs->where('last_status', 'failed')->count();
    @endphp

    <div class="admin-page-head">
        <div><span class="admin-eyebrow">AUTOMATION</span><h1>Quản lý Cron Jobs</h1><p>Quản lý & kiểm tra trạng thái các tác vụ tự động của ShopKey.</p></div>
        <a href="{{ route('admin.cron-jobs.index') }}" class="admin-button"><x-icon name="clock" :size="16" /> Làm mới trạng thái</a>
    </div>

    <div class="admin-stat-grid admin-cron-stats">
        <article><span class="admin-stat-icon admin-stat-icon--teal"><x-icon name="clock" :size="21" /></span><small>TỔNG TÁC VỤ</small><strong>{{ $jobs->count() }}</strong><em>{{ $jobs->where('is_enabled', true)->count() }} tác vụ đang bật</em></article>
        <article><span class="admin-stat-icon admin-stat-icon--blue"><x-icon name="bolt" :size="21" /></span><small>SCHEDULER</small><strong>{{ $healthyCount }}</strong><em>Tín hiệu trong 3 phút gần nhất</em></article>
        <article><span class="admin-stat-icon admin-stat-icon--amber"><x-icon name="orders" :size="21" /></span><small>LƯỢT ĐÃ CHẠY</small><strong>{{ number_format($jobs->sum('run_count')) }}</strong><em>Tổng số lần thực thi được ghi nhận</em></article>
        <article><span class="admin-stat-icon admin-stat-icon--violet"><x-icon name="shield" :size="21" /></span><small>LỖI GẦN NHẤT</small><strong>{{ $failedCount }}</strong><em>Tác vụ cần kiểm tra</em></article>
    </div>

    <section class="admin-panel admin-cron-panel">
        <div class="admin-panel__head"><div><span class="admin-eyebrow">SCHEDULED TASKS</span><h2>Danh sách tác vụ tự động</h2></div><strong>{{ $jobs->count() }} tác vụ</strong></div>
        <div class="admin-table-wrap">
            <table class="admin-table admin-cron-table">
                <thead><tr><th>Tác vụ</th><th>Cấu hình</th><th>Scheduler</th><th>Lần chạy gần nhất</th><th>Lịch chạy</th><th>Mô tả</th><th>Hành động</th></tr></thead>
                <tbody>
                    @foreach($jobs as $job)
                        @php($schedulerState = $job->schedulerState())
                        <tr>
                            <td><div class="admin-cron-name"><span><x-icon name="bolt" :size="17" /></span><div><strong>{{ $job->name }}</strong><code>{{ $job->command }}</code></div></div></td>
                            <td><span class="admin-badge {{ $job->is_enabled ? 'admin-badge--active' : 'admin-badge--disabled' }}">{{ $job->is_enabled ? 'Đang bật' : 'Đã tắt' }}</span></td>
                            <td>
                                <span @class(['admin-badge', 'admin-badge--success' => $schedulerState === 'healthy', 'admin-badge--pending' => $schedulerState === 'stale', 'admin-badge--failed' => $schedulerState === 'not_installed'])>{{ match($schedulerState) { 'healthy' => 'Hoạt động', 'stale' => 'Mất tín hiệu', default => 'Chưa cài' } }}</span>
                                <small class="admin-table-sub">{{ $job->last_scheduler_ping_at?->diffForHumans() ?? 'Chưa nhận heartbeat' }}</small>
                            </td>
                            <td>
                                @if($job->last_started_at)
                                    <span class="admin-cron-run"><i class="is-{{ $job->last_status }}"></i><strong>{{ match($job->last_status) { 'success' => 'Thành công', 'failed' => 'Thất bại', 'running' => 'Đang chạy', default => ucfirst((string) $job->last_status) } }}</strong></span>
                                    <small class="admin-table-sub">{{ $job->last_started_at->diffForHumans() }} · {{ number_format($job->last_duration_ms ?? 0) }} ms</small>
                                @else
                                    <span class="admin-cron-never">Chưa bao giờ chạy</span>
                                @endif
                            </td>
                            <td><code class="admin-cron-expression">{{ $job->expression }}</code><small class="admin-table-sub">{{ $job->frequency_label }}</small></td>
                            <td><span class="admin-cron-description">{{ $job->description }}</span>@if($job->last_message)<small class="admin-table-sub admin-cron-message" title="{{ $job->last_message }}">{{ $job->last_message }}</small>@endif</td>
                            <td><div class="admin-cron-actions"><form action="{{ route('admin.cron-jobs.run', $job) }}" method="POST">@csrf<button class="admin-button admin-button--primary" type="submit"><x-icon name="bolt" :size="14" /> Chạy thử</button></form><form action="{{ route('admin.cron-jobs.toggle', $job) }}" method="POST">@csrf @method('PATCH')<button class="admin-button" type="submit">{{ $job->is_enabled ? 'Tắt' : 'Bật' }}</button></form></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-panel admin-cron-setup">
        <div class="admin-panel__head"><div><span class="admin-eyebrow">SERVER SETUP</span><h2>Cài scheduler trên máy chủ</h2></div></div>
        <div class="admin-cron-setup__body">
            <div class="admin-cron-note"><span><x-icon name="shield" :size="20" /></span><div><strong>Chỉ cần cài một cron duy nhất trên máy chủ</strong><p>Laravel Scheduler sẽ tự quyết định tác vụ nào cần chạy. Trạng thái chuyển sang “Hoạt động” sau khi nhận heartbeat đầu tiên.</p></div></div>
            <label><span>Linux cron — chạy mỗi phút</span><div><code>{{ $schedulerCommand }}</code><button type="button" data-copy-value="{{ $schedulerCommand }}">Sao chép</button></div></label>
            <label><span>Chạy liên tục khi phát triển / Windows</span><div><code>{{ $workerCommand }}</code><button type="button" data-copy-value="{{ $workerCommand }}">Sao chép</button></div></label>
        </div>
    </section>
@endsection
