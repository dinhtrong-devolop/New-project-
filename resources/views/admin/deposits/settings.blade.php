@extends('layouts.admin')

@section('title', 'Cài đặt nạp thẻ')
@section('page_title', 'Cài đặt nạp thẻ')

@section('content')
    <div class="admin-page-head">
        <div><span class="admin-eyebrow">PAYMENT GATEWAY</span><h1>Cấu hình nạp thẻ</h1><p>Quản lý kết nối Naptudong và trạng thái nhận thẻ của toàn hệ thống.</p></div>
        <a href="{{ route('admin.deposits.index') }}" class="admin-button"><x-icon name="orders" :size="16" /> Xem giao dịch</a>
    </div>

    <div class="admin-callback-notice">
        <span><x-icon name="bolt" :size="20" /></span>
        <div><small>CALLBACK URL · CHẤP NHẬN GET HOẶC POST</small><strong>{{ $callbackUrl }}</strong></div>
        <a href="{{ $callbackUrl }}" target="_blank" rel="noreferrer">Kiểm tra URL <x-icon name="arrow-right" :size="15" /></a>
    </div>

    <form action="{{ route('admin.deposits.settings.update') }}" method="POST" class="admin-panel admin-gateway-settings">
        @csrf
        @method('PUT')

        <div class="admin-panel__head">
            <div><span class="admin-eyebrow">NAPTUDONG V2</span><h2>Cài đặt cổng nạp thẻ</h2></div>
            <span class="admin-badge admin-badge--{{ $configuration['is_enabled'] ? 'active' : 'disabled' }}">{{ $configuration['is_enabled'] ? 'Đang bật' : 'Đang tắt' }}</span>
        </div>

        <div class="admin-gateway-settings__body">
            <div class="admin-gateway-toggle">
                <input type="hidden" name="is_enabled" value="0">
                <label class="admin-toggle">
                    <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $configuration['is_enabled']))>
                    <span></span><b>Kích hoạt nạp thẻ cào</b>
                </label>
                <small>Tắt mục này để chặn toàn bộ yêu cầu nạp mới; callback cũ vẫn được xác minh nhưng không thể dùng khi cổng tắt.</small>
            </div>

            <div class="admin-form-grid admin-gateway-grid">
                <label class="admin-field">
                    <span>Website/API đối tác <b>*</b></span>
                    <input type="url" name="provider_url" value="{{ old('provider_url', $configuration['provider_url']) }}" placeholder="https://naptudong.com/chargingws/v2" required>
                    <small>Nhập đầy đủ endpoint nhận thẻ, không có dấu “/” ở cuối.</small>
                </label>
                <label class="admin-field">
                    <span>Chiết khấu nạp thẻ (%) <b>*</b></span>
                    <input type="number" name="discount_percent" value="{{ old('discount_percent', $configuration['discount_percent']) }}" min="0" max="100" step="0.01" required>
                    <small>Mức tham khảo hiển thị cho khách; ví cộng đúng số thực nhận trong callback.</small>
                </label>
                <label class="admin-field">
                    <span>Partner ID</span>
                    <input type="text" name="partner_id" value="{{ old('partner_id', $configuration['partner_id']) }}" maxlength="255" autocomplete="off" placeholder="Nhập Partner ID">
                    <small>Bắt buộc khi kích hoạt cổng nạp thẻ.</small>
                </label>
                <label class="admin-field">
                    <span>Partner Key</span>
                    <input type="password" name="partner_key" value="" minlength="8" maxlength="4096" autocomplete="new-password" placeholder="{{ $configuration['has_partner_key'] ? 'Đã lưu an toàn — để trống nếu không đổi' : 'Nhập Partner Key' }}">
                    <small>{{ $configuration['has_partner_key'] ? 'Khóa hiện tại đang được mã hóa và không hiển thị lại.' : 'Bắt buộc khi kích hoạt cổng nạp thẻ.' }}</small>
                </label>
            </div>
        </div>

        <div class="admin-gateway-settings__footer">
            <div><x-icon name="lock" :size="17" /><span>Partner Key được mã hóa bằng khóa ứng dụng trước khi lưu database.</span></div>
            <button class="admin-button admin-button--primary"><x-icon name="check" :size="16" /> Lưu thay đổi</button>
        </div>
    </form>
@endsection
