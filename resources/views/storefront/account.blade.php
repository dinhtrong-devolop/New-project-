@extends('layouts.storefront')

@section('title', 'Tài khoản - ShopKey')

@section('content')
    <div class="page-hero page-hero--small"><div class="container"><nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><strong>Tài khoản</strong></nav><h1>Trung tâm tài khoản</h1><p>Quản lý hồ sơ, ví, đơn hàng và bảo mật tại một nơi.</p></div></div>

    <section class="section account-page">
        <div class="container account-layout">
            <aside class="account-nav">
                <div class="account-nav__profile"><span>{{ str($user->name)->substr(0, 2)->upper() }}</span><div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div></div>
                <nav><a href="#overview" class="is-active"><x-icon name="grid" :size="18" /> Tổng quan</a><a href="{{ route('orders.index') }}"><x-icon name="orders" :size="18" /> Đơn hàng</a><a href="{{ route('wallet.index') }}"><x-icon name="wallet" :size="18" /> Ví ShopKey</a><a href="{{ route('wallet.deposit.bank') }}"><x-icon name="bolt" :size="18" /> Nạp QR ngân hàng</a><a href="{{ route('wallet.deposit.card') }}"><x-icon name="plus" :size="18" /> Nạp thẻ cào</a><a href="#profile"><x-icon name="user" :size="18" /> Hồ sơ</a><a href="#security"><x-icon name="lock" :size="18" /> Đổi mật khẩu</a></nav>
                <form action="{{ route('logout') }}" method="POST" class="account-logout">@csrf<button type="submit"><x-icon name="arrow-right" :size="18" /> Đăng xuất</button></form>
            </aside>

            <div class="account-content" id="overview">
                @if (session('status'))<div class="form-alert form-alert--success account-alert"><x-icon name="check" :size="18" /> {{ session('status') }}</div>@endif
                @if ($errors->any())<div class="form-alert form-alert--error account-alert"><x-icon name="shield" :size="18" /><div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>@endif

                <div class="account-heading"><div><span class="section-heading__eyebrow">TỔNG QUAN</span><h2>Xin chào, {{ $user->name }}!</h2><p>Thông tin mới nhất trong tài khoản ShopKey của bạn.</p></div><span class="account-status"><i></i> Tài khoản hoạt động</span></div>
                <div class="account-stats">
                    <div><span><x-icon name="wallet" :size="22" /></span><small>Số dư ví</small><strong>{{ number_format($user->wallet?->balance ?? 0, 0, ',', '.') }}₫</strong><a href="{{ route('wallet.index') }}">Xem ví</a></div>
                    <div><span><x-icon name="orders" :size="22" /></span><small>Tổng đơn hàng</small><strong>{{ $user->orders_count }}</strong><a href="{{ route('orders.index') }}">Xem đơn</a></div>
                    <div><span><x-icon name="plus" :size="22" /></span><small>Lần nạp thẻ</small><strong>{{ $user->card_deposits_count }}</strong><a href="{{ route('wallet.deposits') }}">Lịch sử</a></div>
                </div>

                <section class="account-data-card" id="orders">
                    <div class="account-data-card__head"><div><h2>Đơn hàng gần đây</h2><p>Mở chi tiết để xem key hoặc tài khoản đã giao.</p></div><a href="{{ route('orders.index') }}">Xem tất cả</a></div>
                    <div class="account-data-list">
                        @forelse($user->orders as $order)
                            <a href="{{ route('orders.show', $order) }}"><span><strong>{{ $order->order_code }}</strong><small>{{ $order->items_count }} sản phẩm · {{ $order->created_at->format('d/m/Y H:i') }}</small></span><span><strong>{{ number_format($order->total, 0, ',', '.') }}₫</strong><small class="status-text status-text--{{ $order->order_status->value }}">{{ $order->order_status->label() }}</small></span></a>
                        @empty<div class="account-data-empty">Bạn chưa có đơn hàng. <a href="{{ route('products.index') }}">Bắt đầu mua sắm</a></div>@endforelse
                    </div>
                </section>

                <section class="account-data-card">
                    <div class="account-data-card__head"><div><h2>Giao dịch ví gần đây</h2><p>Mọi thay đổi số dư đều có sổ đối soát.</p></div><a href="{{ route('wallet.index') }}">Xem ví</a></div>
                    <div class="account-data-list">
                        @forelse($user->wallet?->transactions ?? [] as $transaction)
                            <div><span><strong>{{ $transaction->description }}</strong><small>{{ $transaction->created_at->format('d/m/Y H:i') }}</small></span><span><strong class="{{ $transaction->amount >= 0 ? 'money-positive' : 'money-negative' }}">{{ $transaction->amount >= 0 ? '+' : '' }}{{ number_format($transaction->amount, 0, ',', '.') }}₫</strong><small>Còn {{ number_format($transaction->balance_after, 0, ',', '.') }}₫</small></span></div>
                        @empty<div class="account-data-empty">Chưa có giao dịch ví.</div>@endforelse
                    </div>
                </section>

                <div class="account-settings-grid">
                    <section class="account-settings-card" id="profile">
                        <div class="account-settings-card__head"><span><x-icon name="user" :size="22" /></span><div><h2>Thông tin cá nhân</h2><p>Cập nhật thông tin dùng cho đơn hàng và hỗ trợ.</p></div></div>
                        <form action="{{ route('account.profile.update') }}" method="POST" class="settings-form">@csrf @method('PATCH')
                            <div class="form-field"><label for="profile_name">Họ và tên</label><div class="form-control @error('name') is-invalid @enderror"><input id="profile_name" name="name" value="{{ old('name', $user->name) }}" required></div>@error('name')<small class="form-error">{{ $message }}</small>@enderror</div>
                            <div class="form-field"><label for="profile_email">Email</label><div class="form-control @error('email') is-invalid @enderror"><input id="profile_email" type="email" name="email" value="{{ old('email', $user->email) }}" required></div>@error('email')<small class="form-error">{{ $message }}</small>@enderror</div>
                            <div class="form-field"><label for="profile_phone">Số điện thoại</label><div class="form-control @error('phone') is-invalid @enderror"><input id="profile_phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Chưa cập nhật"></div>@error('phone')<small class="form-error">{{ $message }}</small>@enderror</div>
                            <button class="button button--primary">Lưu thông tin</button>
                        </form>
                    </section>
                    <section class="account-settings-card" id="security">
                        <div class="account-settings-card__head"><span><x-icon name="lock" :size="22" /></span><div><h2>Đổi mật khẩu</h2><p>Dùng mật khẩu riêng và khó đoán.</p></div></div>
                        <form action="{{ route('account.password.update') }}" method="POST" class="settings-form">@csrf @method('PUT')
                            <div class="form-field"><label>Mật khẩu hiện tại</label><div class="form-control"><input type="password" name="current_password" autocomplete="current-password" required></div></div>
                            <div class="form-field"><label>Mật khẩu mới</label><div class="form-control"><input type="password" name="password" autocomplete="new-password" required></div></div>
                            <div class="form-field"><label>Xác nhận mật khẩu</label><div class="form-control"><input type="password" name="password_confirmation" autocomplete="new-password" required></div></div>
                            <button class="button button--primary">Cập nhật mật khẩu</button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </section>
@endsection
