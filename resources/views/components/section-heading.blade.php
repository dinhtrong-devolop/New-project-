@props(['eyebrow' => null, 'title', 'description' => null, 'href' => null, 'link' => 'Xem tất cả'])

<div class="section-heading">
    <div>
        @if($eyebrow)
            <span class="section-heading__eyebrow">{{ $eyebrow }}</span>
        @endif
        <h2>{{ $title }}</h2>
        @if($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    @if($href)
        <a href="{{ $href }}" class="section-heading__link">{{ $link }} <x-icon name="arrow-right" :size="17" /></a>
    @endif
</div>
