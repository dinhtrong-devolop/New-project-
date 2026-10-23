<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b0b0c">
    <title>@yield('title', 'Quản trị') - {{ $siteSettings->site_name }} Admin</title>
    <link rel="icon" href="{{ $siteSettings->assetUrl('favicon_path') ?: asset('images/shopkey/favicon.svg') }}">
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
    <aside class="admin-sidebar" data-admin-sidebar>
        <div class="admin-sidebar__brand"><x-shopkey-logo light :settings="$siteSettings" /><span>QUẢN TRỊ</span></div>
        <nav class="admin-nav">
            <small>TỔNG QUAN</small>
            <a href="{{ route('admin.dashboard') }}" @class(['is-active' => request()->routeIs('admin.dashboard')])><x-icon name="grid" :size="19" /> Dashboard</a>
            <small>VẬN HÀNH</small>
            <a href="{{ route('admin.products.index') }}" @class(['is-active' => request()->routeIs('admin.products.*')])><x-icon name="box" :size="19" /> Sản phẩm</a>
            <a href="{{ route('admin.inventory.index') }}" @class(['is-active' => request()->routeIs('admin.inventory.*')])><x-icon name="key" :size="19" /> Kho sản phẩm số</a>
            <a href="{{ route('admin.categories.index') }}" @class(['is-active' => request()->routeIs('admin.categories.*')])><x-icon name="grid" :size="19" /> Danh mục</a>
            <a href="{{ route('admin.orders.index') }}" @class(['is-active' => request()->routeIs('admin.orders.*')])><x-icon name="orders" :size="19" /> Đơn hàng</a>
            <small>KHÁCH HÀNG</small>
            <a href="{{ route('admin.users.index') }}" @class(['is-active' => request()->routeIs('admin.users.*')])><x-icon name="user" :size="19" /> Người dùng</a>
            <a href="{{ route('admin.deposits.index') }}" @class(['is-active' => request()->routeIs('admin.deposits.index')])><x-icon name="wallet" :size="19" /> Giao dịch nạp</a>
            <a href="{{ route('admin.deposits.settings.edit') }}" @class(['is-active' => request()->routeIs('admin.deposits.settings.*')])><x-icon name="shield" :size="19" /> Cài đặt nạp thẻ</a>
            <a href="{{ route('admin.vouchers.index') }}" @class(['is-active' => request()->routeIs('admin.vouchers.*')])><x-icon name="sparkles" :size="19" /> Voucher</a>
            <small>TÀI CHÍNH</small>
            <a href="{{ route('admin.bank-accounts.index') }}" @class(['is-active' => request()->routeIs('admin.bank-accounts.index', 'admin.bank-accounts.edit')])><x-icon name="wallet" :size="19" /> Danh sách tài khoản</a>
            <a href="{{ route('admin.bank-accounts.create') }}" @class(['is-active' => request()->routeIs('admin.bank-accounts.create')])><x-icon name="plus" :size="19" /> Thêm tài khoản mới</a>
            <a href="{{ route('admin.bank-deposits.index') }}" @class(['is-active' => request()->routeIs('admin.bank-deposits.*')])><x-icon name="orders" :size="19" /> Hóa đơn ngân hàng</a>
            <small>LỊCH SỬ</small>
            <details class="admin-nav-group" @if(request()->routeIs('admin.history.*')) open @endif>
                <summary @class(['is-active' => request()->routeIs('admin.history.*')])><x-icon name="clock" :size="19" /><span>Lịch sử</span><x-icon name="chevron-down" :size="14" /></summary>
                <div class="admin-nav-group__items">
                    <a href="{{ route('admin.history.transactions') }}" @class(['is-active' => request()->routeIs('admin.history.transactions')])><i></i>Lịch sử giao dịch</a>
                    <a href="{{ route('admin.history.orders') }}" @class(['is-active' => request()->routeIs('admin.history.orders')])><i></i>Lịch sử đặt dịch vụ</a>
                    <a href="{{ route('admin.history.card-deposits') }}" @class(['is-active' => request()->routeIs('admin.history.card-deposits')])><i></i>Lịch sử nạp thẻ cào</a>
                    <a href="{{ route('admin.history.voucher-usages') }}" @class(['is-voucher', 'is-active' => request()->routeIs('admin.history.voucher-usages')])><i></i>Lịch sử dùng mã giảm giá</a>
                </div>
            </details>
            <small>HỆ THỐNG</small>
            <a href="{{ route('admin.api-docs') }}" @class(['is-active' => request()->routeIs('admin.api-docs')])><x-icon name="bolt" :size="19" /> Hướng dẫn API App</a>
            <a href="{{ route('admin.cron-jobs.index') }}" @class(['is-active' => request()->routeIs('admin.cron-jobs.*')])><x-icon name="clock" :size="19" /> Cron Jobs</a>
            <a href="{{ route('admin.settings.general.edit') }}" @class(['is-active' => request()->routeIs('admin.settings.general.*')])><x-icon name="sparkles" :size="19" /> Cài đặt chung</a>
            <a href="{{ route('admin.support.index') }}" @class(['is-active' => request()->routeIs('admin.support.*')])><x-icon name="support" :size="19" /> Hỗ trợ khách hàng @if($supportAdminUnreadCount > 0)<b class="admin-nav-badge">{{ min($supportAdminUnreadCount, 99) }}</b>@endif</a>
        </nav>
        <div class="admin-sidebar__footer">
            <span>{{ str(auth()->user()->name)->substr(0, 2)->upper() }}</span>
            <div><strong>{{ auth()->user()->name }}</strong><small>Quản trị viên</small></div>
            <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" aria-label="Đăng xuất"><x-icon name="arrow-right" :size="18" /></button></form>
        </div>
    </aside>
    <div class="admin-sidebar__backdrop" data-admin-sidebar-backdrop hidden></div>

    <div class="admin-shell">
        <header class="admin-topbar">
            <button type="button" class="admin-menu-toggle" data-admin-menu-toggle aria-label="Mở menu"><x-icon name="menu" :size="22" /></button>
            <div><small>TRUNG TÂM QUẢN TRỊ SHOPKEY</small><strong>@yield('page_title', 'Tổng quan')</strong></div>
            <nav><x-theme-toggle /><a href="{{ route('home') }}" target="_blank"><x-icon name="home" :size="17" /> Xem cửa hàng</a><span>{{ now()->format('d/m/Y') }}</span></nav>
        </header>

        <main class="admin-main">
            @if (session('status'))
                <div class="admin-alert admin-alert--success"><x-icon name="check" :size="18" /> {{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="admin-alert admin-alert--error"><x-icon name="shield" :size="18" /><div><strong>Vui lòng kiểm tra lại dữ liệu</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
