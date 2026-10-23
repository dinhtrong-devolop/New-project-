@extends('layouts.storefront')

@section('title', $product->name.' - ShopKey')
@section('meta_description', $product->short_description)

@section('content')
    <div class="container product-breadcrumb-wrap">
        <nav class="breadcrumb breadcrumb--dark"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('products.index') }}">Sản phẩm</a><span>/</span><a href="{{ route('products.index', ['category' => $product->category?->slug]) }}">{{ $product->category?->name }}</a><span>/</span><strong>{{ $product->name }}</strong></nav>
    </div>

    <section class="product-detail">
        <div class="container">
            @if (session('status'))<div class="form-alert form-alert--success storefront-alert"><x-icon name="check" :size="18" /> {{ session('status') }}</div>@endif
            @if ($errors->any())<div class="form-alert form-alert--error storefront-alert"><x-icon name="shield" :size="18" /><div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>@endif
        </div>
        <div class="container product-detail__grid">
            <div class="product-gallery">
                <div class="product-gallery__main"><x-product-art :product="$product" large /></div>
                <div class="product-gallery__thumbs">
                    @if($product->images->isNotEmpty())
                        @foreach($product->images as $image)<button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-gallery-image="{{ asset('storage/'.$image->path) }}" data-gallery-alt="{{ $image->alt_text ?: $product->name }}"><img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text ?: $product->name }}"></button>@endforeach
                    @else
                        <button type="button" class="is-active"><x-product-art :product="$product" /></button>
                    @endif
                    <button type="button" class="gallery-info-card"><x-icon name="shield" :size="23" /><span>Bảo vệ<br>đơn hàng</span></button>
                    <button type="button" class="gallery-info-card"><x-icon name="bolt" :size="23" /><span>Giao hàng<br>tự động</span></button>
                </div>
            </div>

            <div class="product-summary">
                <div class="product-summary__meta"><a href="{{ route('products.index', ['category' => $product->category?->slug]) }}">{{ $product->category?->name }}</a><span>SKU: {{ $product->sku }}</span></div>
                <h1>{{ $product->name }}</h1>
                <div class="product-summary__rating"><span><x-icon name="star" :size="16" /> {{ number_format((float) $product->rating_average, 1) }}</span><a href="#reviews">{{ number_format($product->rating_count) }} đánh giá</a><i></i><span>Đã bán {{ number_format($product->sold_count) }}</span></div>
                <p class="product-summary__description">{{ $product->short_description }}</p>
                <div class="product-price-box">
                    <div><small>Giá sản phẩm</small><strong>{{ number_format($product->price, 0, ',', '.') }}₫</strong>@if($product->original_price)<del>{{ number_format($product->original_price, 0, ',', '.') }}₫</del>@endif</div>
                    @if($product->original_price)<span>Tiết kiệm {{ number_format($product->original_price - $product->price, 0, ',', '.') }}₫</span>@endif
                </div>
                <div class="product-facts">
                    <div><x-icon name="bolt" :size="20" /><span><small>Hình thức giao</small><strong>Giao tự động 24/7</strong></span></div>
                    <div><x-icon name="box" :size="20" /><span><small>Tình trạng</small><strong class="text-success">Còn {{ $product->stock_count }} sản phẩm</strong></span></div>
                    <div><x-icon name="shield" :size="20" /><span><small>Bảo hành</small><strong>{{ $product->warranty_days ? $product->warranty_days.' ngày' : 'Theo nhà phát hành' }}</strong></span></div>
                </div>
                <form action="{{ route('cart.store') }}" method="POST" class="product-purchase-form">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="purchase-row"><label>Số lượng <span class="quantity-control"><button type="button" data-qty-minus><x-icon name="minus" :size="15" /></button><input name="quantity" type="number" value="1" min="1" max="{{ $product->stock_count }}" data-qty-input><button type="button" data-qty-plus><x-icon name="plus" :size="15" /></button></span></label></div>
                    <div class="purchase-actions">
                        <button name="action" value="buy_now" class="button button--primary button--large" @disabled($product->stock_count < 1)><x-icon name="bolt" :size="19" /> Mua ngay</button>
                        <button name="action" value="add" class="button button--outline button--large" @disabled($product->stock_count < 1)><x-icon name="cart" :size="19" /> Thêm vào giỏ</button>
                    </div>
                </form>
                <div class="secure-checkout-note"><x-icon name="lock" :size="16" /> Thanh toán bảo toàn · Key chỉ hiển thị trong đơn hàng của bạn</div>
            </div>
        </div>
    </section>

    <section class="section product-information">
        <div class="container product-information__grid">
            <article class="content-card">
                <div class="content-tabs"><button class="is-active">Thông tin sản phẩm</button><button>Hướng dẫn kích hoạt</button><button>Chính sách bảo hành</button></div>
                <div class="content-card__body"><h2>Thông tin về {{ $product->name }}</h2><p>{{ $product->description }}</p><h3>Bạn sẽ nhận được</h3><ul class="check-list"><li><x-icon name="check" :size="17" /> Key hoặc thông tin tài khoản được giao tự động</li><li><x-icon name="check" :size="17" /> Lịch sử bàn giao theo từng đơn</li><li><x-icon name="check" :size="17" /> Hỗ trợ trong thời gian bảo hành</li></ul></div>
            </article>
            <aside class="seller-card"><span class="seller-card__badge"><x-icon name="shield" :size="19" /> SHOPKEY VERIFIED</span><div class="seller-card__avatar">SK</div><h3>ShopKey Official</h3><p>Nhà phân phối sản phẩm số được xác minh</p><div class="seller-card__stats"><span><strong>4.9/5</strong><small>Đánh giá</small></span><span><strong>99.8%</strong><small>Hoàn tất</small></span></div><a href="{{ route('products.index') }}" class="button button--outline button--full">Xem toàn bộ sản phẩm</a></aside>
        </div>
    </section>

    <section class="section review-section" id="reviews">
        <div class="container product-information__grid">
            <div>
                <x-section-heading eyebrow="KHÁCH HÀNG ĐÃ MUA" title="Đánh giá sản phẩm" />
                <div class="review-list">
                    @forelse($product->reviews as $review)
                        <article><div><strong>{{ $review->user->name }}</strong><span>{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span></div><p>{{ $review->comment ?: 'Sản phẩm đúng mô tả và được giao nhanh.' }}</p><small>Mua hàng xác thực · {{ $review->published_at?->diffForHumans() }}</small></article>
                    @empty
                        <div class="review-empty">Chưa có đánh giá thực tế cho sản phẩm này.</div>
                    @endforelse
                </div>
            </div>
            <aside class="review-form-card">
                @if($reviewableOrderItem)
                    <h3>Viết đánh giá</h3><p>Bạn đã mua sản phẩm này. Hãy chia sẻ trải nghiệm.</p>
                    <form action="{{ route('reviews.store') }}" method="POST">@csrf<input type="hidden" name="order_item_id" value="{{ $reviewableOrderItem->id }}"><label>Điểm đánh giá<select name="rating" required><option value="5">5 - Rất tốt</option><option value="4">4 - Tốt</option><option value="3">3 - Bình thường</option><option value="2">2 - Chưa tốt</option><option value="1">1 - Không hài lòng</option></select></label><label>Nhận xét<textarea name="comment" rows="5" maxlength="2000" placeholder="Chia sẻ trải nghiệm của bạn"></textarea></label><button class="button button--primary button--full">Gửi đánh giá</button></form>
                @else
                    <span class="review-lock"><x-icon name="shield" :size="24" /></span><h3>Đánh giá đã xác minh</h3><p>Chỉ khách hàng hoàn tất mua sản phẩm mới có thể gửi một đánh giá.</p>
                @endif
            </aside>
        </div>
    </section>

    <section class="section section--soft"><div class="container"><x-section-heading eyebrow="CÓ THỂ BẠN THÍCH" title="Sản phẩm liên quan" :href="route('products.index', ['category' => $product->category?->slug])" /><div class="product-grid product-grid--five">@foreach($relatedProducts as $relatedProduct)<x-product-card :product="$relatedProduct" />@endforeach</div></div></section>
@endsection
