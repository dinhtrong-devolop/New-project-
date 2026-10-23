@props(['product', 'rank' => null])

@php
    $discount = $product->original_price && $product->original_price > $product->price
        ? (int) round((1 - ($product->price / $product->original_price)) * 100)
        : null;
@endphp

<article class="product-card">
    <a class="product-card__visual" href="{{ route('products.show', $product) }}" aria-label="Xem {{ $product->name }}">
        <x-product-art :product="$product" />
        <div class="product-card__badges">
            @if($rank)
                <span class="badge badge--rank">#{{ $rank }}</span>
            @elseif($product->is_featured)
                <span class="badge badge--featured">Nổi bật</span>
            @endif
            @if($discount)
                <span class="badge badge--sale">-{{ $discount }}%</span>
            @endif
        </div>
        <span class="product-card__quick"><x-icon name="arrow-right" :size="17" /></span>
    </a>
    <div class="product-card__body">
        <a class="product-card__category" href="{{ route('products.index', ['category' => $product->category?->slug]) }}">{{ $product->category?->name }}</a>
        <h3><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3>
        <div class="product-card__rating">
            <span><x-icon name="star" :size="14" /> {{ number_format((float) $product->rating_average, 1) }}</span>
            <span>Đã bán {{ number_format($product->sold_count) }}</span>
        </div>
        <div class="product-card__footer">
            <div class="product-card__price">
                <strong>{{ number_format($product->price, 0, ',', '.') }}₫</strong>
                @if($product->original_price)
                    <del>{{ number_format($product->original_price, 0, ',', '.') }}₫</del>
                @endif
            </div>
            <span class="stock-dot {{ ($product->stock_count ?? 0) > 0 ? '' : 'stock-dot--empty' }}">
                {{ ($product->stock_count ?? 0) > 0 ? 'Còn hàng' : 'Hết hàng' }}
            </span>
        </div>
    </div>
</article>
