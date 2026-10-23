@extends('layouts.storefront')

@section('title', 'Quên mật khẩu - ShopKey')

@section('content')
    <section class="auth-page">
        <div class="container">
            <x-auth-shell eyebrow="KHÔI PHỤC TÀI KHOẢN" title="Quên mật khẩu?" description="Nhập email đăng ký, ShopKey sẽ gửi cho bạn liên kết đặt lại mật khẩu có thời hạn.">
                <form action="{{ route('password.email') }}" method="POST" class="auth-form">
                    @csrf
                    <div class="form-field">
                        <label for="email">Email tài khoản</label>
                        <div class="form-control @error('email') is-invalid @enderror"><x-icon name="user" :size="18" /><input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="ban@email.com" autocomplete="email" required autofocus></div>
                        @error('email')<small class="form-error">{{ $message }}</small>@enderror
                    </div>
                    <button type="submit" class="button button--primary auth-submit"><x-icon name="arrow-right" :size="18" /> Gửi liên kết đặt lại</button>
                </form>
                <p class="auth-switch"><a href="{{ route('login') }}">Quay lại đăng nhập</a></p>
            </x-auth-shell>
        </div>
    </section>
@endsection
