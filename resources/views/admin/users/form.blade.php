@extends('layouts.admin')

@section('title', $user->exists ? 'Sửa người dùng' : 'Thêm người dùng')
@section('page_title', $user->exists ? 'Sửa người dùng' : 'Thêm người dùng')

@section('content')
    <div class="admin-page-head">
        <div><span class="admin-eyebrow">CUSTOMERS</span><h1>{{ $user->exists ? 'Sửa người dùng' : 'Thêm người dùng mới' }}</h1><p>{{ $user->exists ? 'Cập nhật hồ sơ, vai trò, trạng thái hoặc đặt mật khẩu mới.' : 'Tạo tài khoản và ví ShopKey cho thành viên mới.' }}</p></div>
        <a href="{{ $user->exists ? route('admin.users.show', $user) : route('admin.users.index') }}" class="admin-button">Hủy bỏ</a>
    </div>

    <form action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST" class="admin-user-form">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <section class="admin-panel admin-form-card">
            <div class="admin-form-card__head"><span class="admin-eyebrow">THÔNG TIN TÀI KHOẢN</span><h2>Hồ sơ người dùng</h2><p>Email và số điện thoại không được trùng với tài khoản khác.</p></div>
            <div class="admin-form-grid">
                <label class="admin-field admin-field--full"><span>Họ và tên *</span><input name="name" value="{{ old('name', $user->name) }}" maxlength="120" required autocomplete="name"></label>
                <label class="admin-field"><span>Email *</span><input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required autocomplete="email"></label>
                <label class="admin-field"><span>Số điện thoại</span><input name="phone" value="{{ old('phone', $user->phone) }}" maxlength="20" autocomplete="tel" placeholder="Ví dụ: 0901234567"></label>
            </div>
        </section>

        <section class="admin-panel admin-form-card">
            <div class="admin-form-card__head"><span class="admin-eyebrow">PHÂN QUYỀN</span><h2>Vai trò và trạng thái</h2><p>Tài khoản bị khóa sẽ tự động đăng xuất và không thể sử dụng website.</p></div>
            <div class="admin-form-grid admin-user-access-grid">
                <label class="admin-field"><span>Vai trò *</span><select name="role" required>@foreach(App\Enums\UserRole::cases() as $role)<option value="{{ $role->value }}" @selected(old('role', $user->role?->value ?? App\Enums\UserRole::Customer->value) === $role->value)>{{ $role->label() }}</option>@endforeach</select><small>Quản trị viên có toàn quyền truy cập khu vực admin.</small></label>
                <label class="admin-field"><span>Trạng thái *</span><select name="status" required><option value="active" @selected(old('status', $user->locked_at ? 'locked' : 'active') === 'active')>Hoạt động</option><option value="locked" @selected(old('status', $user->locked_at ? 'locked' : 'active') === 'locked')>Đã khóa</option></select><small>Khóa tài khoản nhưng vẫn giữ nguyên đơn hàng và dữ liệu đối soát.</small></label>
            </div>
        </section>

        <section class="admin-panel admin-form-card">
            <div class="admin-form-card__head"><span class="admin-eyebrow">BẢO MẬT</span><h2>{{ $user->exists ? 'Đổi mật khẩu' : 'Mật khẩu đăng nhập' }}</h2><p>{{ $user->exists ? 'Để trống cả hai ô nếu không muốn thay đổi mật khẩu hiện tại.' : 'Mật khẩu cần đáp ứng chính sách bảo mật của ShopKey.' }}</p></div>
            <div class="admin-form-grid">
                <label class="admin-field"><span>Mật khẩu {{ $user->exists ? '' : '*' }}</span><input type="password" name="password" {{ $user->exists ? '' : 'required' }} autocomplete="new-password"></label>
                <label class="admin-field"><span>Xác nhận mật khẩu {{ $user->exists ? '' : '*' }}</span><input type="password" name="password_confirmation" {{ $user->exists ? '' : 'required' }} autocomplete="new-password"></label>
            </div>
        </section>

        <div class="admin-user-form__actions">
            @if($user->exists && ! $user->is(auth()->user()))
                <button type="submit" form="delete-user-form" class="admin-button admin-button--danger" onclick="return confirm('Bạn chắc chắn muốn xóa người dùng này?')"><x-icon name="trash" :size="16" /> Xóa người dùng</button>
            @else
                <span></span>
            @endif
            <div><a href="{{ $user->exists ? route('admin.users.show', $user) : route('admin.users.index') }}" class="admin-button">Hủy bỏ</a><button type="submit" class="admin-button admin-button--primary"><x-icon name="check" :size="16" /> {{ $user->exists ? 'Lưu thay đổi' : 'Tạo người dùng' }}</button></div>
        </div>
    </form>

    @if($user->exists && ! $user->is(auth()->user()))
        <form id="delete-user-form" action="{{ route('admin.users.destroy', $user) }}" method="POST" hidden>@csrf @method('DELETE')</form>
    @endif
@endsection
