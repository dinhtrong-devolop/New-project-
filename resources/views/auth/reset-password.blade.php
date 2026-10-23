@extends('layouts.storefront')

@section('title', 'Đặt lại mật khẩu - ShopKey')

@section('content')
    <section class="auth-page">
        <div class="container">
            <x-auth-shell eyebrow="MẬT KHẨU MỚI" title="Đặt lại mật khẩu" description="Chọn mật khẩu mới cho tài khoản ShopKey của bạn.">
                <form action="{{ route('password.update') }}" method="POST" class="auth-form">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="form-field">
                        <label for="email">Email tài khoản</label>
                        <div class="form-control @error('email') is-invalid @enderror"><x-icon name="user" :size="18" /><input id="email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required></div>
                        @error('email')<small class="form-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label for="password">Mật khẩu mới</label>
                        <div class="form-control @error('password') is-invalid @enderror"><x-icon name="lock" :size="18" /><input id="password" type="password" name="password" placeholder="Tối thiểu 8 ký tự" autocomplete="new-password" required><button type="button" data-password-toggle="password">Hiện</button></div>
                        @error('password')<small class="form-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label for="password_confirmation">Nhập lại mật khẩu mới</label>
                        <div class="form-control"><x-icon name="lock" :size="18" /><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required><button type="button" data-password-toggle="password_confirmation">Hiện</button></div>
                    </div>
                    <button type="submit" class="button button--primary auth-submit"><x-icon name="shield" :size="18" /> Cập nhật mật khẩu</button>
                </form>
            </x-auth-shell>
        </div>
    </section>
@endsection
