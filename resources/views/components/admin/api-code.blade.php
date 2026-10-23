@props(['title', 'code'])

<div class="admin-api-code">
    <div><span>{{ $title }}</span><button type="button" data-copy-value="{{ $code }}">Sao chép</button></div>
    <pre><code>{{ $code }}</code></pre>
</div>
