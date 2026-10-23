@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard vận hành')

@section('content')
    <div class="admin-page-head">
        <div><span class="admin-eyebrow">TỔNG QUAN HỆ THỐNG</span><h1>Chào {{ auth()->user()->name }}</h1><p>Dữ liệu vận hành ShopKey được cập nhật lúc {{ $generatedAt->format('H:i, d/m/Y') }}.</p></div>
        <a href="{{ route('admin.products.create') }}" class="admin-button admin-button--primary"><x-icon name="plus" :size="18" /> Thêm sản phẩm</a>
    </div>

    <section class="admin-stat-grid">
        <article><span class="admin-stat-icon admin-stat-icon--teal"><x-icon name="user" :size="22" /></span><small>Khách hàng</small><strong>{{ number_format($stats['users']) }}</strong><em>Tài khoản customer</em></article>
        <article><span class="admin-stat-icon admin-stat-icon--blue"><x-icon name="box" :size="22" /></span><small>Sản phẩm</small><strong>{{ number_format($stats['products']) }}</strong><em>{{ $stats['lowStock'] }} sản phẩm sắp hết kho</em></article>
        <article><span class="admin-stat-icon admin-stat-icon--violet"><x-icon name="orders" :size="22" /></span><small>Đơn hàng</small><strong>{{ number_format($stats['orders']) }}</strong><em>{{ $stats['ordersToday'] }} đơn hôm nay</em></article>
        <article><span class="admin-stat-icon admin-stat-icon--amber"><x-icon name="wallet" :size="22" /></span><small>Doanh thu</small><strong>{{ number_format($stats['revenue'], 0, ',', '.') }}₫</strong><em>{{ number_format($stats['revenueToday'], 0, ',', '.') }}₫ hôm nay</em></article>
    </section>

    <div class="admin-dashboard-grid">
        <section class="admin-panel admin-panel--chart">
            <div class="admin-panel__head"><div><span class="admin-eyebrow">7 NGÀY GẦN NHẤT</span><h2>Biểu đồ doanh thu</h2></div><strong>{{ number_format($chart->sum('value'), 0, ',', '.') }}₫</strong></div>
            <div class="admin-chart">
                @foreach ($chart as $point)
                    <div class="admin-chart__column"><div><span style="height: {{ max(5, round(($point['value'] / $chartMaximum) * 100)) }}%"><i>{{ $point['value'] ? number_format($point['value'] / 1000).'K' : '0' }}</i></span></div><small>{{ $point['label'] }}</small></div>
                @endforeach
            </div>
        </section>
        <section class="admin-panel">
            <div class="admin-panel__head"><div><span class="admin-eyebrow">CẦN XỬ LÝ</span><h2>Tín hiệu vận hành</h2></div></div>
            <div class="admin-signal-list">
                <a href="{{ route('admin.deposits.index', ['status' => 'pending']) }}"><span class="admin-stat-icon admin-stat-icon--amber"><x-icon name="wallet" :size="20" /></span><div><strong>Nạp tiền đang chờ</strong><small>Cần kiểm tra trạng thái nhà cung cấp</small></div><b>{{ $stats['pendingDeposits'] }}</b></a>
                <a href="{{ route('admin.inventory.index', ['status' => 'available']) }}"><span class="admin-stat-icon admin-stat-icon--blue"><x-icon name="key" :size="20" /></span><div><strong>Sản phẩm sắp hết kho</strong><small>Tồn kho khả dụng từ 5 trở xuống</small></div><b>{{ $stats['lowStock'] }}</b></a>
                <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}"><span class="admin-stat-icon admin-stat-icon--violet"><x-icon name="orders" :size="20" /></span><div><strong>Đơn hàng hôm nay</strong><small>Phát sinh trong ngày hiện tại</small></div><b>{{ $stats['ordersToday'] }}</b></a>
            </div>
        </section>
    </div>

    <div class="admin-dashboard-grid admin-dashboard-grid--lower">
        <section class="admin-panel">
            <div class="admin-panel__head"><div><span class="admin-eyebrow">GẦN ĐÂY</span><h2>Đơn hàng mới</h2></div><a href="{{ route('admin.orders.index') }}">Xem tất cả</a></div>
            <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead><tbody>
                @forelse ($recentOrders as $order)<tr><td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_code }}</a></td><td>{{ $order->user->email }}</td><td>{{ number_format($order->total, 0, ',', '.') }}₫</td><td><span class="admin-badge admin-badge--{{ $order->order_status->value }}">{{ $order->order_status->label() }}</span></td></tr>@empty<tr><td colspan="4"><div class="admin-empty">Chưa có đơn hàng.</div></td></tr>@endforelse
            </tbody></table></div>
        </section>
        <section class="admin-panel">
            <div class="admin-panel__head"><div><span class="admin-eyebrow">TOP CATALOG</span><h2>Sản phẩm bán chạy</h2></div><a href="{{ route('admin.products.index') }}">Quản lý</a></div>
            <div class="admin-ranking">
                @foreach ($topProducts as $product)<a href="{{ route('admin.products.edit', $product) }}"><span>{{ $loop->iteration }}</span><div><strong>{{ $product->name }}</strong><small>{{ $product->category->name }} · {{ $product->sku }}</small></div><b>{{ number_format($product->sold_count) }}</b></a>@endforeach
            </div>
        </section>
    </div>
@endsection
