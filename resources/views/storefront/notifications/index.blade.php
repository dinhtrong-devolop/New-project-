@extends('layouts.storefront')
@section('title', 'Thông báo - ShopKey')
@section('content')
    <div class="page-hero page-hero--small"><div class="container"><nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><strong>Thông báo</strong></nav><h1>Thông báo của bạn</h1><p>Cập nhật đơn hàng, giao sản phẩm và hoạt động tài khoản.</p></div></div>
    <section class="section"><div class="container customer-panel notification-panel">
        @if(session('status'))<div class="form-alert form-alert--success storefront-alert">{{ session('status') }}</div>@endif
        <div class="customer-panel__head"><div><h2>Trung tâm thông báo</h2><p>{{ $notifications->total() }} thông báo</p></div><form action="{{ route('notifications.read-all') }}" method="POST">@csrf<button class="button button--outline">Đánh dấu đã đọc</button></form></div>
        <div class="notification-list">@forelse($notifications as $notification)<a href="{{ $notification->data['url'] ?? route('account.index') }}" class="{{ $notification->read_at ? '' : 'is-unread' }}"><span><x-icon name="bell" :size="20" /></span><div><strong>{{ $notification->data['title'] ?? 'Thông báo ShopKey' }}</strong><p>{{ $notification->data['message'] ?? '' }}</p><small>{{ $notification->created_at->diffForHumans() }}</small></div></a>@empty<div class="account-data-empty">Bạn chưa có thông báo.</div>@endforelse</div>{{ $notifications->links() }}
    </div></section>
@endsection
