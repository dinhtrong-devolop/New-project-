@extends('layouts.storefront')

@section('mainClass', 'storefront-home')

@section('title', $siteSettings->site_name.' - Chợ sản phẩm số chính hãng')

@section('content')
    <section class="neo-hero">
        <span class="neo-hero__glow neo-hero__glow--left"></span>
        <span class="neo-hero__glow neo-hero__glow--right"></span>
        <div class="container neo-hero__inner">
            <div class="neo-hero__copy">
                <span class="neo-eyebrow">SHOPKEY DIGITAL MARKET</span>
                <h1>Nền tảng giao dịch<br>sản phẩm số <span>thế hệ mới.</span></h1>
                <p>Mua key phần mềm, gói thuê bao, thẻ quà tặng và tài khoản số từ kho hàng được kiểm soát. Thanh toán được bảo vệ, giao hàng tức thì và luôn có hỗ trợ khi cần.</p>
                <div class="neo-hero__actions">
                    <a href="{{ route('products.index') }}" class="button button--primary button--large">Khám phá ngay <x-icon name="arrow-right" :size="18" /></a>
                    <a href="#featured" class="neo-text-link">Xem sản phẩm nổi bật</a>
                </div>
                <div class="neo-trust-row">
                    <div><span><x-icon name="shield" :size="18" /></span><p><strong>Thanh toán được bảo vệ</strong><small>Xử lý tranh chấp khi cần</small></p></div>
                    <div><span><x-icon name="orders" :size="18" /></span><p><strong>Bàn giao có lưu vết</strong><small>Theo dõi ngay trong đơn hàng</small></p></div>
                    <div><span><x-icon name="support" :size="18" /></span><p><strong>Hỗ trợ trong tài khoản</strong><small>Trao đổi riêng tư, dễ xem lại</small></p></div>
                </div>
            </div>

            <div class="market-device market-device--image">
                <img src="{{ $siteSettings->assetUrl('cover_path') ?: asset('images/shopkey/marketplace-hero.png') }}" alt="{{ $siteSettings->site_name }} marketplace" width="1536" height="1024" fetchpriority="high">
            </div>
        </div>
    </section>

    <section class="category-rail">
        <div class="container category-rail__inner">
            <span>Khám phá theo danh mục</span>
            @foreach($categories as $category)<a href="{{ route('products.index', ['category' => $category->slug]) }}">{{ $category->name }} <x-icon name="arrow-right" :size="13" /></a>@endforeach
        </div>
    </section>

    @if($siteSettings->assetUrl('banner_path'))
        <section class="site-promo-banner"><div class="container"><img src="{{ $siteSettings->assetUrl('banner_path') }}" alt="Banner {{ $siteSettings->site_name }}" loading="eager"></div></section>
    @endif

    <section class="section neo-product-section" id="featured">
        <div class="container">
            <x-section-heading eyebrow="ĐƯỢC QUAN TÂM" title="Sản phẩm nổi bật" description="Sản phẩm số phổ biến, sẵn kho và có thể nhận ngay sau thanh toán." :href="route('products.index', ['sort' => 'featured'])" />
            <div class="product-grid product-grid--six">
                @forelse($featuredProducts as $product)<x-product-card :product="$product" />@empty<div class="empty-state empty-state--wide"><x-icon name="box" :size="30" /><strong>Catalog đang được cập nhật</strong></div>@endforelse
            </div>
        </div>
    </section>

    <section class="market-stats"><div class="container market-stats__grid"><div><strong>{{ number_format($marketStats['products']) }}+</strong><span>Sản phẩm đang bán</span></div><div><strong>{{ number_format($marketStats['deliveries']) }}+</strong><span>Lượt bàn giao</span></div><div><strong>{{ number_format($marketStats['inStock']) }}+</strong><span>Sản phẩm sẵn kho</span></div><div><strong>24/7</strong><span>Giao hàng tự động</span></div></div></section>

    <section class="section section--soft">
        <div class="container">
            <x-section-heading eyebrow="XU HƯỚNG TUẦN NÀY" title="Sản phẩm bán chạy" description="Những lựa chọn được cộng đồng ShopKey tin dùng nhiều nhất." :href="route('products.index', ['sort' => 'best_selling'])" />
            <div class="product-grid product-grid--six">@foreach($bestSellingProducts as $product)<x-product-card :product="$product" :rank="$loop->iteration" />@endforeach</div>
        </div>
    </section>

    <section class="section latest-market">
        <div class="container split-catalog">
            <div><x-section-heading eyebrow="VỪA CẬP NHẬT" title="Sản phẩm mới" :href="route('products.index', ['sort' => 'latest'])" /><div class="compact-product-list">@foreach($newProducts as $product)<a href="{{ route('products.show', $product) }}" class="compact-product"><x-product-art :product="$product" /><span><small>{{ $product->category?->name }}</small><strong>{{ $product->name }}</strong><b>{{ number_format($product->price, 0, ',', '.') }}₫</b></span><x-icon name="arrow-right" :size="17" /></a>@endforeach</div></div>
            <div><x-section-heading eyebrow="GIÁ TỐT" title="Ưu đãi nổi bật" :href="route('products.index', ['sort' => 'price_asc'])" /><div class="compact-product-list">@foreach($promotionProducts as $product)<a href="{{ route('products.show', $product) }}" class="compact-product"><x-product-art :product="$product" /><span><small>{{ $product->category?->name }}</small><strong>{{ $product->name }}</strong><b>{{ number_format($product->price, 0, ',', '.') }}₫ @if($product->original_price)<del>{{ number_format($product->original_price, 0, ',', '.') }}₫</del>@endif</b></span><x-icon name="arrow-right" :size="17" /></a>@endforeach</div></div>
        </div>
    </section>

    <section class="section neo-process" id="how-it-works">
        <div class="container">
            <x-section-heading eyebrow="MUA NHANH, NHẬN NGAY" title="Giao dịch đơn giản trong 4 bước" description="Mọi thao tác được thiết kế rõ ràng cho sản phẩm số." />
            <div class="process-grid">
                <div class="process-card"><span>01</span><div class="process-card__icon"><x-icon name="search" :size="26" /></div><h3>Chọn sản phẩm</h3><p>Tìm sản phẩm phù hợp và kiểm tra thông tin.</p></div>
                <div class="process-card"><span>02</span><div class="process-card__icon"><x-icon name="cart" :size="26" /></div><h3>Thêm vào giỏ</h3><p>Chọn số lượng và áp dụng mã ưu đãi.</p></div>
                <div class="process-card"><span>03</span><div class="process-card__icon"><x-icon name="wallet" :size="26" /></div><h3>Thanh toán</h3><p>Xác nhận bằng số dư ví được bảo vệ.</p></div>
                <div class="process-card"><span>04</span><div class="process-card__icon"><x-icon name="key" :size="26" /></div><h3>Nhận sản phẩm</h3><p>Key xuất hiện ngay trong chi tiết đơn hàng.</p></div>
            </div>
        </div>
    </section>
@endsection
