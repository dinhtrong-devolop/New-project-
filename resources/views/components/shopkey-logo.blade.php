@props(['compact' => false, 'light' => false, 'footer' => false, 'settings' => null])

@php
    $settings = $settings ?? ($siteSettings ?? null);
    $siteName = $settings?->site_name ?: 'ShopKey';
    $logoUrl = $settings?->assetUrl($footer ? 'footer_logo_path' : 'logo_path');
    $darkLogoUrl = $footer ? null : $settings?->assetUrl('dark_logo_path');
    $logoUrl = $logoUrl ?: $darkLogoUrl;
    $hasDarkVariant = filled($darkLogoUrl) && $darkLogoUrl !== $logoUrl;
@endphp

<a href="{{ route('home') }}" {{ $attributes->class(['brand-logo', 'brand-logo--light' => $light, 'brand-logo--custom' => filled($logoUrl)]) }} aria-label="{{ $siteName }} - Trang chủ">
    @if($logoUrl)
        <img @class(['brand-logo__custom-image', 'brand-logo__custom-image--default' => $hasDarkVariant]) src="{{ $logoUrl }}" alt="{{ $siteName }}" loading="eager">
        @if($hasDarkVariant)
            <img class="brand-logo__custom-image brand-logo__custom-image--dark" src="{{ $darkLogoUrl }}" alt="{{ $siteName }}" loading="eager">
        @endif
    @else
        <span class="brand-logo__mark">
            <svg viewBox="0 0 40 40" aria-hidden="true">
                <path d="M23.7 6.2a10.2 10.2 0 1 0 3 19.9l2.7 2.7h4.2v4.1h4.2v-7.8L30.7 18a10.2 10.2 0 0 0-7-11.8Zm-8.2 12.5a3.6 3.6 0 1 1 0-7.2 3.6 3.6 0 0 1 0 7.2Z" fill="currentColor"/>
            </svg>
        </span>
        @unless($compact)
            <span class="brand-logo__text">@if(strtolower($siteName) === 'shopkey')Shop<span>Key</span>@else{{ $siteName }}@endif</span>
        @endunless
    @endif
</a>
