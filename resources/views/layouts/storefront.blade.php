<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b0b0c">
    <meta name="description" content="@yield('meta_description', $siteSettings->site_description)">
    @if($siteSettings->site_keywords)<meta name="keywords" content="{{ $siteSettings->site_keywords }}">@endif
    <title>@yield('title', $siteSettings->site_name.' - Digital Marketplace')</title>
    <link rel="icon" href="{{ $siteSettings->assetUrl('favicon_path') ?: asset('images/shopkey/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Science+Gothic:wght@300..900&amp;display=swap" rel="stylesheet">
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="storefront-body">
    <header class="site-header" data-site-header>
        <div class="header-utility">
            <div class="container header-utility__inner">
                <div class="header-locale"><strong>VN</strong><span>Tiếng Việt</span><x-icon name="chevron-down" :size="13" /></div>
                <form action="{{ route('products.index') }}" method="GET" class="header-search" role="search">
                    <x-icon name="search" :size="17" />
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm sản phẩm số..." aria-label="Tìm kiếm sản phẩm">
                    <kbd>⌘K</kbd>
                </form>
                <div class="header-utility__actions">
                    <x-theme-toggle />
                    @auth
                        <a href="{{ route('notifications.index') }}" class="utility-icon" aria-label="Thông báo"><x-icon name="bell" :size="17" />@if($notificationCount > 0)<b>{{ min($notificationCount, 99) }}</b>@endif</a>
                        <details class="user-menu">
                            <summary class="utility-login"><x-icon name="user" :size="16" /><span>{{ auth()->user()->name }}</span><x-icon name="chevron-down" :size="13" /></summary>
                            <div class="user-menu__panel">
                                <a href="{{ route('account.index') }}"><x-icon name="user" :size="17" /> Tài khoản</a>
                                <a href="{{ route('wallet.index') }}"><x-icon name="wallet" :size="17" /> Ví ShopKey</a>
                                <a href="{{ route('wallet.deposit.card') }}"><x-icon name="plus" :size="17" /> Nạp tiền</a>
                                <a href="{{ route('orders.index') }}"><x-icon name="orders" :size="17" /> Đơn hàng</a>
                                @if(auth()->user()->role === \App\Enums\UserRole::Admin)<a href="{{ route('admin.dashboard') }}"><x-icon name="grid" :size="17" /> Quản trị</a>@endif
                                <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit"><x-icon name="arrow-right" :size="17" /> Đăng xuất</button></form>
                                <a href="{{ route('wallet.deposit.bank') }}"><x-icon name="bolt" :size="17" /> Nạp QR ngân hàng</a>
                            </div>
                        </details>
                    @else
                        <a href="{{ route('login') }}" class="utility-login"><x-icon name="arrow-right" :size="16" /><span>Đăng nhập</span></a>
                    @endauth
                </div>
            </div>
        </div>

        <div class="header-navigation">
            <div class="container header-navigation__inner">
                <button type="button" class="icon-button mobile-menu-button" data-mobile-menu-toggle aria-label="Mở menu" aria-controls="mobile-navigation" aria-expanded="false"><x-icon name="menu" :size="23" /></button>
                <x-shopkey-logo light :settings="$siteSettings" />
                <nav class="primary-nav" aria-label="Điều hướng chính">
                    <a href="{{ route('products.index') }}" @class(['is-active' => request()->routeIs('products.*')])>Sản phẩm</a>
                    <a href="{{ route('products.index', ['sort' => 'best_selling']) }}">Bán chạy</a>
                    <a href="{{ route('products.index', ['category' => 'game-keys']) }}">Game & Gift Card</a>
                    <a href="#support">Hỗ trợ</a>
                </nav>
                <nav class="navigation-actions" aria-label="Tài khoản và giỏ hàng">
                    <x-theme-toggle class="theme-toggle--mobile-nav" />
                    @auth
                        <a href="{{ route('wallet.index') }}" class="nav-wallet"><x-icon name="wallet" :size="16" /><span>{{ number_format(auth()->user()->wallet?->balance ?? 0, 0, ',', '.') }}₫</span></a>
                        @if(auth()->user()->role === \App\Enums\UserRole::Admin)<a href="{{ route('admin.dashboard') }}" class="nav-explore">Quản trị</a>@endif
                    @else
                        <a href="{{ route('register') }}" class="nav-explore">Tạo tài khoản</a>
                    @endauth
                    <a href="{{ route('cart.index') }}" class="nav-cart" aria-label="Giỏ hàng"><x-icon name="cart" :size="19" /><span>Giỏ hàng</span><b>{{ $cartItemCount }}</b></a>
                </nav>
            </div>
        </div>

        <div class="mobile-search-wrap container">
            <form action="{{ route('products.index') }}" method="GET" class="header-search header-search--mobile"><x-icon name="search" :size="17" /><input type="search" name="search" placeholder="Tìm sản phẩm..."><button type="submit" aria-label="Tìm kiếm"><x-icon name="arrow-right" :size="17" /></button></form>
        </div>
    </header>

    <div class="mobile-drawer" id="mobile-navigation" data-mobile-menu hidden>
            <div class="mobile-drawer__head"><x-shopkey-logo light :settings="$siteSettings" /><button type="button" class="icon-button" data-mobile-menu-close aria-label="Đóng menu"><x-icon name="close" :size="24" /></button></div>
            <x-theme-toggle label class="theme-toggle--drawer" />
            <a href="{{ route('home') }}"><x-icon name="home" :size="19" /> Trang chủ</a>
            <a href="{{ route('products.index') }}"><x-icon name="grid" :size="19" /> Sản phẩm</a>
            <a href="{{ route('products.index', ['sort' => 'best_selling']) }}"><x-icon name="bolt" :size="19" /> Bán chạy</a>
            <a href="{{ route('products.index', ['category' => 'game-keys']) }}"><x-icon name="sparkles" :size="19" /> Game Keys</a>
            <a href="{{ route('cart.index') }}"><x-icon name="cart" :size="19" /> Giỏ hàng <b>{{ $cartItemCount }}</b></a>
            @auth
                <a href="{{ route('account.index') }}"><x-icon name="user" :size="19" /> Tài khoản</a>
                <a href="{{ route('orders.index') }}"><x-icon name="orders" :size="19" /> Đơn hàng</a>
                <a href="{{ route('wallet.index') }}"><x-icon name="wallet" :size="19" /> Ví ShopKey</a>
                <form action="{{ route('logout') }}" method="POST" class="mobile-drawer__logout">@csrf<button type="submit"><x-icon name="arrow-right" :size="19" /> Đăng xuất</button></form>
            @else
                <a href="{{ route('login') }}"><x-icon name="user" :size="19" /> Đăng nhập</a>
                <a href="{{ route('register') }}"><x-icon name="sparkles" :size="19" /> Đăng ký</a>
            @endauth
    </div>
    <div class="mobile-drawer-backdrop" data-mobile-menu-backdrop hidden></div>

    <main class="@yield('mainClass')">@yield('content')</main>

    <footer class="site-footer" id="support">
        <div class="container site-footer__grid" id="policies">
            <div class="site-footer__brand"><x-shopkey-logo light footer :settings="$siteSettings" /><p>{{ $siteSettings->footerText('footer_description') }}</p><div class="site-footer__trust"><x-icon name="shield" :size="18" /> {{ $siteSettings->footerText('footer_trust_text') }}</div></div>
            @foreach($siteSettings->resolvedFooterColumns() as $column)
                <div><h3>{{ $column['title'] }}</h3>@foreach($column['links'] as $link)<a href="{{ $link['url'] }}" @if(str_starts_with($link['url'], 'http')) target="_blank" rel="noreferrer" @endif>{{ $link['label'] }}</a>@endforeach</div>
            @endforeach
        </div>
        <div class="container site-footer__bottom"><span>{{ $siteSettings->footerText('footer_copyright') }}</span><span>{{ $siteSettings->footerText('footer_bottom_text') }}</span></div>
    </footer>
    @auth
        @if(auth()->user()->role === \App\Enums\UserRole::Admin)
            <a href="{{ route('admin.support.index') }}" class="floating-support" aria-label="Hộp thư hỗ trợ"><x-icon name="support" :size="23" />@if($supportAdminUnreadCount ?? 0)<b>{{ min($supportAdminUnreadCount, 99) }}</b>@endif</a>
        @else
            <button type="button" class="floating-support" data-support-chat-toggle aria-label="{{ $siteSettings->chat_label }}" aria-expanded="false"><x-icon name="support" :size="23" />@if($supportUnreadCount > 0)<b data-support-unread-badge>{{ min($supportUnreadCount, 99) }}</b>@endif</button>
            <aside class="support-chat" data-support-chat data-messages-url="{{ route('support.chat.messages') }}" data-send-url="{{ route('support.chat.messages.store') }}" data-read-url="{{ route('support.chat.read') }}" hidden>
                <header><span><x-icon name="support" :size="21" /></span><div><strong>{{ $siteSettings->chat_label }}</strong><small><i></i> ShopKey Support · phản hồi sớm nhất</small></div><button type="button" data-support-chat-close aria-label="Đóng chat"><x-icon name="close" :size="20" /></button></header>
                <div class="support-chat__messages" data-support-chat-messages><div class="support-chat__loading"><x-icon name="clock" :size="21" /><span>Đang tải hội thoại...</span></div></div>
                <div class="support-chat__error" data-support-chat-error hidden></div>
                <form data-support-chat-form><textarea name="message" rows="2" maxlength="2000" required placeholder="Nhập nội dung cần hỗ trợ..."></textarea><button type="submit" aria-label="Gửi tin nhắn"><x-icon name="arrow-right" :size="19" /></button></form>
                <footer><x-icon name="lock" :size="13" /> Hội thoại riêng tư theo tài khoản đăng nhập</footer>
            </aside>
        @endif
    @else
        <a href="{{ route('login') }}" class="floating-support" aria-label="Đăng nhập để nhắn tin"><x-icon name="support" :size="23" /></a>
    @endauth
</body>
</html>
