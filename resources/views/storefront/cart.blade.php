@extends('layouts.storefront')

@section('title', 'Giỏ hàng - ShopKey')

@section('content')
    <div class="page-hero page-hero--small">
        <div class="container">
            <nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><strong>Giỏ hàng</strong></nav>
            <h1>Giỏ hàng của bạn</h1><p>Kiểm tra sản phẩm, số lượng và mã ưu đãi trước khi thanh toán.</p>
        </div>
    </div>

    <section class="section cart-page">
        <div class="container">
            @if (session('status'))
                <div class="form-alert form-alert--success storefront-alert"><x-icon name="check" :size="18" /> {{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="form-alert form-alert--error storefront-alert"><x-icon name="shield" :size="18" /><div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
            @endif
        </div>

        <div class="container cart-layout">
            <div class="cart-main">
                <div class="cart-progress"><span class="is-active"><b>1</b> Giỏ hàng</span><i></i><span><b>2</b> Thanh toán</span><i></i><span><b>3</b> Hoàn tất</span></div>
                @forelse($items as $item)
                    <article class="cart-item">
                        <a href="{{ route('products.show', $item->product) }}" class="cart-item__art"><x-product-art :product="$item->product" /></a>
                        <div class="cart-item__info">
                            <small>{{ $item->product->category?->name }}</small>
                            <h2><a href="{{ route('products.show', $item->product) }}">{{ $item->product->name }}</a></h2>
                            <span><x-icon name="bolt" :size="15" /> Còn {{ $item->product->stock_count }} · Giao tự động</span>
                        </div>
                        <form action="{{ route('cart.update', $item) }}" method="POST" class="cart-quantity-form">
                            @csrf @method('PATCH')
                            <div class="quantity-control"><button type="button" data-qty-minus><x-icon name="minus" :size="14" /></button><input name="quantity" type="number" value="{{ $item->quantity }}" min="1" max="{{ $item->product->stock_count }}" data-qty-input><button type="button" data-qty-plus><x-icon name="plus" :size="14" /></button></div>
                            <button type="submit" class="cart-update-button">Cập nhật</button>
                        </form>
                        <strong class="cart-item__price">{{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}₫</strong>
                        <form action="{{ route('cart.destroy', $item) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="cart-item__remove" aria-label="Xóa sản phẩm"><x-icon name="trash" :size="18" /></button></form>
                    </article>
                @empty
                    <div class="cart-empty">
                        <span><x-icon name="cart" :size="40" /></span><h2>Giỏ hàng đang trống</h2><p>Khám phá catalog ShopKey và chọn sản phẩm số phù hợp với bạn.</p><a href="{{ route('products.index') }}" class="button button--primary">Khám phá sản phẩm <x-icon name="arrow-right" :size="18" /></a>
                    </div>
                @endforelse
                <a href="{{ route('products.index') }}" class="continue-shopping"><x-icon name="arrow-right" class="icon--reverse" :size="17" /> Tiếp tục mua sắm</a>
            </div>

            <aside class="order-summary">
                <h2>Tóm tắt đơn hàng</h2>
                <div class="summary-line"><span>Tạm tính</span><strong>{{ number_format($totals['subtotal'], 0, ',', '.') }}₫</strong></div>
                <div class="summary-line summary-line--discount"><span>Giảm giá</span><strong>-{{ number_format($totals['discount'], 0, ',', '.') }}₫</strong></div>
                @if ($totals['voucher'])
                    <div class="voucher-applied"><span><x-icon name="check" :size="15" /> {{ $totals['voucher']->code }}</span><form action="{{ route('cart.voucher.destroy') }}" method="POST">@csrf @method('DELETE')<button type="submit">Bỏ mã</button></form></div>
                @else
                    <form action="{{ route('cart.voucher.store') }}" method="POST" class="voucher-box">@csrf<span>Mã ưu đãi</span><div><input name="voucher" value="{{ old('voucher') }}" placeholder="Nhập mã voucher"><button type="submit">Áp dụng</button></div></form>
                @endif
                <div class="summary-total"><span>Tổng thanh toán</span><strong>{{ number_format($totals['total'], 0, ',', '.') }}₫</strong></div>
                <a href="{{ $items->isEmpty() ? route('products.index') : route('checkout.index') }}" class="button button--primary button--large button--full">{{ $items->isEmpty() ? 'Chọn sản phẩm' : 'Tiến hành thanh toán' }} <x-icon name="arrow-right" :size="18" /></a>
                <p class="summary-security"><x-icon name="shield" :size="17" /> Được bảo vệ bởi ShopKey Protect</p>
            </aside>
        </div>
    </section>
@endsection
