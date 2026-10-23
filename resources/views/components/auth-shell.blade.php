@props([
    'eyebrow' => 'SHOPKEY ACCOUNT',
    'title',
    'description',
])

<div class="auth-layout">
    <section class="auth-card">
        <a href="{{ route('home') }}" class="auth-back"><x-icon name="arrow-right" class="icon--reverse" :size="16" /> Về trang chủ</a>
        <div class="auth-card__heading">
            <span class="section-heading__eyebrow">{{ $eyebrow }}</span>
            <h1>{{ $title }}</h1>
            <p>{{ $description }}</p>
        </div>

        @if (session('status'))
            <div class="form-alert form-alert--success"><x-icon name="check" :size="18" /> {{ session('status') }}</div>
        @endif

        {{ $slot }}
    </section>

    <aside class="auth-showcase">
        <span class="auth-showcase__eyebrow"><x-icon name="sparkles" :size="17" /> SHOPKEY MEMBER</span>
        <h2>Một tài khoản.<br><span>Mọi sản phẩm số.</span></h2>
        <p>Quản lý key, lịch sử giao hàng, ví và hỗ trợ bảo hành trong một không gian an toàn.</p>
        <div class="auth-showcase__points">
            <div><span><x-icon name="bolt" :size="20" /></span><strong>Nhận hàng tức thì</strong><small>Sản phẩm nằm ngay trong tài khoản sau thanh toán.</small></div>
            <div><span><x-icon name="shield" :size="20" /></span><strong>Lịch sử minh bạch</strong><small>Thông tin bàn giao và bảo hành được lưu có kiểm soát.</small></div>
            <div><span><x-icon name="support" :size="20" /></span><strong>Hỗ trợ tập trung</strong><small>Không cần tìm lại email hoặc thông tin mua hàng cũ.</small></div>
        </div>
        <div class="auth-showcase__seal"><x-icon name="key" :size="28" /><span><b>SHOPKEY</b><small>DIGITAL ACCESS, SECURED</small></span></div>
    </aside>
</div>
