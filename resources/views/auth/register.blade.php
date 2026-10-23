@extends('layouts.storefront')

@section('title', 'Đăng ký - ShopKey')
@section('meta_description', 'Tạo tài khoản ShopKey để mua và quản lý sản phẩm số an toàn.')

@section('content')
    <section class="auth-page">
        <div class="container">
            <x-auth-shell eyebrow="TẠO TÀI KHOẢN" title="Bắt đầu với ShopKey" description="Tạo tài khoản để nhận hàng tự động và lưu trữ sản phẩm số an toàn.">
                <form action="{{ route('register.store') }}" method="POST" class="auth-form">
                    @csrf
                    <div class="form-row">
                        <div class="form-field">
                            <label for="name">Họ và tên</label>
                            <div class="form-control @error('name') is-invalid @enderror"><x-icon name="user" :size="18" /><input id="name" name="name" value="{{ old('name') }}" placeholder="Nguyễn Văn A" autocomplete="name" required autofocus></div>
                            @error('name')<small class="form-error">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-field">
                            <label for="phone">Số điện thoại <small>(không bắt buộc)</small></label>
                            <div class="form-control @error('phone') is-invalid @enderror"><x-icon name="support" :size="18" /><input id="phone" name="phone" value="{{ old('phone') }}" placeholder="09xxxxxxxx" autocomplete="tel"></div>
                            @error('phone')<small class="form-error">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="email">Email</label>
                        <div class="form-control @error('email') is-invalid @enderror"><x-icon name="user" :size="18" /><input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="ban@email.com" autocomplete="email" required></div>
                        @error('email')<small class="form-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="password">Mật khẩu</label>
                            <div class="form-control @error('password') is-invalid @enderror"><x-icon name="lock" :size="18" /><input id="password" type="password" name="password" placeholder="Tối thiểu 8 ký tự" autocomplete="new-password" required><button type="button" data-password-toggle="password">Hiện</button></div>
                            @error('password')<small class="form-error">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-field">
                            <label for="password_confirmation">Nhập lại mật khẩu</label>
                            <div class="form-control"><x-icon name="lock" :size="18" /><input id="password_confirmation" type="password" name="password_confirmation" placeholder="Nhập lại mật khẩu" autocomplete="new-password" required><button type="button" data-password-toggle="password_confirmation">Hiện</button></div>
                        </div>
                    </div>
                    <label class="form-check form-check--terms"><input type="checkbox" name="terms" value="1" @checked(old('terms'))><span>Tôi đồng ý với <a href="#">điều khoản sử dụng</a> và <a href="#">chính sách bảo mật</a> của ShopKey.</span></label>
                    @error('terms')<small class="form-error form-error--standalone">{{ $message }}</small>@enderror
                    <button type="submit" class="button button--primary auth-submit"><x-icon name="sparkles" :size="18" /> Tạo tài khoản ShopKey</button>
                </form>
                <p class="auth-switch">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></p>
            </x-auth-shell>
        </div>
    </section>
@endsection
