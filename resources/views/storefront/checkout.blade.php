@extends('layouts.storefront')

@section('title', 'Thanh toán - ShopKey')

@section('content')
    <div class="page-hero page-hero--small">
        <div class="container">
            <nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('cart.index') }}">Giỏ hàng</a><span>/</span><strong>Thanh toán</strong></nav>
            <h1>Thanh toán an toàn</h1><p>Trừ ví, tạo đơn và giao sản phẩm số trong một giao dịch bảo toàn dữ liệu.</p>
        </div>
    </div>

    <section class="section checkout-page">
        <div class="container">
            @if ($errors->any())
                <div class="form-alert form-alert--error storefront-alert"><x-icon name="shield" :size="18" /><div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
            @endif
        </div>

        <form action="{{ route('checkout.store') }}" method="POST" class="container checkout-layout">
            @csrf
            <input type="hidden" name="payment_method" value="wallet">
            <div class="checkout-main">
                @guest
                    <div class="checkout-notice"><span><x-icon name="user" :size="21" /></span><div><strong>Bạn cần đăng nhập</strong><p>Đăng nhập để thanh toán bằng ví và nhận sản phẩm số.</p></div><a href="{{ route('login') }}" class="button button--outline">Đăng nhập</a></div>
                @endguest
                <section class="checkout-card">
                    <div class="checkout-card__heading"><span>01</span><div><h2>Thông tin nhận hàng</h2><p>Sản phẩm số được lưu trong chi tiết đơn hàng của tài khoản.</p></div></div>
                    <div class="form-grid">
                        <label>Họ và tên<input type="text" value="{{ auth()->user()?->name }}" readonly placeholder="Đăng nhập để tiếp tục"></label>
                        <label>Email<input type="email" value="{{ auth()->user()?->email }}" readonly placeholder="email@example.com"></label>
                        <label class="form-grid__full">Ghi chú đơn hàng<textarea name="customer_note" rows="3" maxlength="1000" placeholder="Thông tin cần lưu ý khi kích hoạt (không bắt buộc)">{{ old('customer_note') }}</textarea></label>
                    </div>
                </section>
                <section class="checkout-card">
                    <div class="checkout-card__heading"><span>02</span><div><h2>Phương thức thanh toán</h2><p>Thanh toán trực tiếp bằng số dư ví ShopKey.</p></div></div>
                    <label class="payment-method is-selected"><input type="radio" checked disabled><span class="payment-method__icon"><x-icon name="wallet" :size="23" /></span><span><strong>Ví ShopKey</strong><small>Số dư: {{ number_format($wallet?->balance ?? 0, 0, ',', '.') }}₫</small></span><b>Khuyên dùng</b></label>
                    @auth<a href="{{ route('wallet.deposit.bank') }}" class="deposit-hint"><x-icon name="plus" :size="17" /> Nạp nhanh qua QR ngân hàng</a>@endauth
                </section>
                <section class="checkout-card checkout-card--assurance"><x-icon name="shield" :size="26" /><div><strong>ShopKey Protect</strong><p>Nếu kho, ví hoặc giao key gặp lỗi, toàn bộ checkout được rollback và không tạo đơn thanh toán dở dang.</p></div></section>
            </div>
            <aside class="order-summary order-summary--checkout">
                <h2>Đơn hàng của bạn</h2>
                <div class="checkout-items">
                    @forelse($items as $item)
                        <div><x-product-art :product="$item->product" /><span><strong>{{ $item->product->name }}</strong><small>Số lượng: {{ $item->quantity }}</small></span><b>{{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}₫</b></div>
                    @empty
                        <div class="checkout-empty"><x-icon name="box" :size="25" /><span><strong>Chưa có sản phẩm</strong><small>Quay lại catalog để chọn sản phẩm.</small></span></div>
                    @endforelse
                </div>
                <div class="summary-line"><span>Tạm tính</span><strong>{{ number_format($totals['subtotal'], 0, ',', '.') }}₫</strong></div>
                <div class="summary-line summary-line--discount"><span>Giảm giá</span><strong>-{{ number_format($totals['discount'], 0, ',', '.') }}₫</strong></div>
                @if($totals['voucher'])<div class="checkout-voucher"><x-icon name="check" :size="15" /> Voucher {{ $totals['voucher']->code }}</div>@endif
                <div class="summary-total"><span>Tổng cộng</span><strong>{{ number_format($totals['total'], 0, ',', '.') }}₫</strong></div>
                @auth
                    <button type="submit" class="button button--primary button--large button--full" @disabled($items->isEmpty())><x-icon name="lock" :size="17" /> Xác nhận thanh toán</button>
                @else
                    <a href="{{ route('login') }}" class="button button--primary button--large button--full"><x-icon name="user" :size="17" /> Đăng nhập để thanh toán</a>
                @endauth
                <p class="summary-terms">Bằng việc thanh toán, bạn đồng ý với điều khoản mua hàng và chính sách hoàn tiền của ShopKey.</p>
            </aside>
        </form>
    </section>
@endsection
