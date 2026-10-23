@extends('layouts.storefront')
@section('title', 'Đơn '.$order->order_code.' - ShopKey')
@section('content')
    <div class="page-hero page-hero--small"><div class="container"><nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('orders.index') }}">Đơn hàng</a><span>/</span><strong>{{ $order->order_code }}</strong></nav><h1>Chi tiết đơn hàng</h1><p>Sản phẩm số chỉ hiển thị cho chủ sở hữu đơn hàng.</p></div></div>
    <section class="section"><div class="container order-detail-page">
        @if(session('status'))<div class="form-alert form-alert--success storefront-alert"><x-icon name="check" :size="18" /> {{ session('status') }}</div>@endif
        @if($errors->any())<div class="form-alert form-alert--error storefront-alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <div class="order-overview"><div><small>Mã đơn hàng</small><strong>{{ $order->order_code }}</strong></div><div><small>Ngày mua</small><strong>{{ $order->created_at->format('d/m/Y H:i') }}</strong></div><div><small>Thanh toán</small><strong>{{ $order->payment_status->label() }}</strong></div><div><small>Trạng thái</small><strong>{{ $order->order_status->label() }}</strong></div><div><small>Tổng tiền</small><strong>{{ number_format($order->total, 0, ',', '.') }}₫</strong></div></div>
        <div class="order-detail-layout"><div class="order-delivery-list">
            @foreach($order->items as $item)
                <article class="order-delivery-card">
                    <div class="order-delivery-card__head"><div><x-product-art :product="$item->product" /><span><small>{{ $item->product_sku }}</small><h2>{{ $item->product_name }}</h2><p>{{ $item->quantity }} × {{ number_format($item->unit_price, 0, ',', '.') }}₫ · Bảo hành {{ $item->warranty_days }} ngày</p></span></div><span class="customer-status customer-status--completed">{{ $item->delivery_status->label() }}</span></div>
                    <div class="delivery-credentials"><div class="delivery-credentials__title"><x-icon name="lock" :size="18" /><span><strong>Thông tin sản phẩm đã giao</strong><small>Không chia sẻ nội dung này với người khác.</small></span></div>
                        @forelse($item->deliveries as $delivery)
                            <div class="credential-box"><div>@foreach($delivery->encrypted_payload as $label => $value)<p><span>{{ str($label)->replace('_', ' ')->title() }}</span><code>{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</code></p>@endforeach</div><small>Giao lúc {{ $delivery->delivered_at?->format('d/m/Y H:i:s') }}</small></div>
                        @empty<div class="delivery-pending">Sản phẩm đang chờ giao thủ công. Bộ phận hỗ trợ sẽ cập nhật tại đây.</div>@endforelse
                    </div>
                    @if(!$item->review)<a href="{{ route('products.show', $item->product) }}#reviews" class="order-review-link"><x-icon name="star" :size="16" /> Đánh giá sản phẩm</a>@else<span class="order-reviewed"><x-icon name="check" :size="16" /> Đã đánh giá {{ $item->review->rating }}/5</span>@endif
                </article>
            @endforeach
        </div><aside class="order-summary order-summary--static"><h2>Tổng kết thanh toán</h2><div class="summary-line"><span>Tạm tính</span><strong>{{ number_format($order->subtotal, 0, ',', '.') }}₫</strong></div><div class="summary-line"><span>Giảm giá @if($order->voucher_code)({{ $order->voucher_code }})@endif</span><strong>-{{ number_format($order->discount_amount, 0, ',', '.') }}₫</strong></div><div class="summary-total"><span>Tổng cộng</span><strong>{{ number_format($order->total, 0, ',', '.') }}₫</strong></div><p class="summary-security"><x-icon name="shield" :size="17" /> Mã giao hàng được mã hóa trong cơ sở dữ liệu</p><a href="mailto:{{ $siteSettings->contact_email }}?subject={{ urlencode('Hỗ trợ đơn '.$order->order_code) }}" class="button button--outline button--full">Yêu cầu hỗ trợ</a></aside></div>
    </div></section>
@endsection
