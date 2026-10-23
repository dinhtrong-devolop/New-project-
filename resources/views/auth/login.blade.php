@extends('layouts.storefront')

@section('title', 'Đăng nhập - ShopKey')
@section('meta_description', 'Đăng nhập ShopKey để quản lý đơn hàng, sản phẩm số và ví của bạn.')

@section('content')
    <section class="auth-page">
        <div class="container">
            <x-auth-shell title="Chào mừng bạn trở lại" description="Đăng nhập để tiếp tục quản lý sản phẩm và đơn hàng ShopKey.">
                <form action="{{ route('login.store') }}" method="POST" class="auth-form">
                    @csrf
                    <div class="form-field">
                        <label for="email">Email</label>
                        <div class="form-control @error('email') is-invalid @enderror"><x-icon name="user" :size="18" /><input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="ban@email.com" autocomplete="email" required autofocus></div>
                        @error('email')<small class="form-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <div class="form-label-row"><label for="password">Mật khẩu</label><a href="{{ route('password.request') }}">Quên mật khẩu?</a></div>
                        <div class="form-control @error('password') is-invalid @enderror"><x-icon name="lock" :size="18" /><input id="password" type="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required><button type="button" data-password-toggle="password">Hiện</button></div>
                        @error('password')<small class="form-error">{{ $message }}</small>@enderror
                    </div>
                    <label class="form-check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Giữ tôi đăng nhập trên thiết bị này</span></label>
                    <button type="submit" class="button button--primary auth-submit"><x-icon name="lock" :size="18" /> Đăng nhập an toàn</button>
                </form>
                <p class="auth-switch">Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký miễn phí</a></p>
            </x-auth-shell>
        </div>
    </section>
@endsection
