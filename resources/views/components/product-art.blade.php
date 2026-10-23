@props(['product', 'large' => false])

@php
    $slug = $product->category?->slug ?? 'other-digital';
    $art = match ($slug) {
        'windows-keys' => ['#2563eb', '#22d3ee', 'WIN', '11'],
        'office-keys' => ['#ea580c', '#fb7185', 'OFFICE', '24'],
        'game-keys' => ['#7c3aed', '#ec4899', 'PLAY', 'PC'],
        'gift-cards' => ['#db2777', '#f97316', 'GIFT', '★'],
        'software' => ['#059669', '#06b6d4', 'SAFE', 'PRO'],
        'subscriptions' => ['#4f46e5', '#8b5cf6', 'PLUS', '∞'],
        'digital-accounts' => ['#0891b2', '#2563eb', 'ID', '✓'],
        default => ['#334155', '#0f766e', 'SK', '+'],
    };
    $primaryImage = $product->relationLoaded('images')
        ? ($product->images->firstWhere('is_primary', true) ?? $product->images->first())
        : null;
@endphp

<div class="product-art {{ $large ? 'product-art--large' : '' }} {{ $primaryImage ? 'product-art--uploaded' : '' }}" style="--art-a: {{ $art[0] }}; --art-b: {{ $art[1] }};">
    @if($primaryImage)
        <img class="product-art__image" src="{{ asset('storage/'.$primaryImage->path) }}" alt="{{ $primaryImage->alt_text ?: $product->name }}" loading="{{ $large ? 'eager' : 'lazy' }}">
    @else
        <span class="product-art__grid" aria-hidden="true"></span>
        <span class="product-art__orb product-art__orb--one" aria-hidden="true"></span>
        <span class="product-art__orb product-art__orb--two" aria-hidden="true"></span>
        <span class="product-art__chip"><x-icon name="key" :size="$large ? 28 : 19" /> DIGITAL</span>
        <strong class="product-art__title">{{ $art[2] }}</strong>
        <span class="product-art__code">{{ $art[3] }}</span>
        <span class="product-art__brand">SHOPKEY VERIFIED</span>
    @endif
</div>
