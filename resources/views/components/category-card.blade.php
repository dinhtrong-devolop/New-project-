@props(['category'])

@php
    $meta = match ($category->slug) {
        'windows-keys' => ['WIN', 'tone-blue'],
        'office-keys' => ['DOC', 'tone-orange'],
        'game-keys' => ['GAME', 'tone-violet'],
        'gift-cards' => ['GIFT', 'tone-pink'],
        'software' => ['APP', 'tone-green'],
        'subscriptions' => ['PRO', 'tone-indigo'],
        'digital-accounts' => ['ID', 'tone-cyan'],
        default => ['DIGI', 'tone-slate'],
    };
@endphp

<a href="{{ route('products.index', ['category' => $category->slug]) }}" class="category-card {{ $meta[1] }}">
    <span class="category-card__icon {{ $category->image_path ? 'category-card__icon--image' : '' }}">@if($category->image_path)<img src="{{ asset('storage/'.$category->image_path) }}" alt="{{ $category->name }}" loading="lazy">@else{{ $meta[0] }}@endif</span>
    <span class="category-card__content">
        <strong>{{ $category->name }}</strong>
        <small>{{ $category->active_products_count ?? 0 }} sản phẩm</small>
    </span>
    <x-icon name="arrow-right" :size="18" />
</a>
