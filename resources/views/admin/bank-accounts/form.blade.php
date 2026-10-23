@extends('layouts.admin')

@section('title', $account->exists ? 'Cấu hình ngân hàng' : 'Thêm tài khoản ngân hàng')
@section('page_title', $account->exists ? 'Cấu hình ngân hàng' : 'Thêm tài khoản ngân hàng')

@section('content')
    <div class="admin-page-head">
        <div><span class="admin-eyebrow">TÀI CHÍNH · SEPAY</span><h1>{{ $account->exists ? 'Cấu hình tài khoản ngân hàng' : 'Thêm tài khoản ngân hàng' }}</h1><p>Tạo tài khoản nhận tiền và bật xác nhận tự động qua SePay webhook.</p></div>
        <a href="{{ route('admin.bank-accounts.index') }}" class="admin-button"><x-icon name="arrow-right" :size="16" /> Danh sách tài khoản</a>
    </div>

    @if($callbackUrl)
        <div class="admin-callback-notice">
            <span><x-icon name="bolt" :size="20" /></span>
            <div><small>SEPAY WEBHOOK URL · PHƯƠNG THỨC POST</small><strong>{{ $callbackUrl }}</strong></div>
            <button type="button" class="admin-copy-button" data-copy-value="{{ $callbackUrl }}">Sao chép</button>
        </div>
    @else
        <div class="admin-provider-notice"><span><x-icon name="shield" :size="21" /></span><div><strong>Webhook URL được tạo sau khi lưu</strong><p>SePay hiện hỗ trợ miễn phí số lượng giao dịch theo chính sách của nhà cung cấp. ShopKey sử dụng QR VietQR và webhook xác thực.</p></div><a href="https://developer.sepay.vn/vi/sepay-webhooks/tich-hop-webhook" target="_blank" rel="noreferrer">Hướng dẫn SePay</a></div>
    @endif

    <form action="{{ $account->exists ? route('admin.bank-accounts.update', $account) : route('admin.bank-accounts.store') }}" method="POST" class="admin-bank-form">
        @csrf
        @if($account->exists) @method('PUT') @endif

        <section class="admin-panel admin-bank-section">
            <div class="admin-panel__head"><div><span class="admin-eyebrow">BƯỚC 1</span><h2>Thông tin tài khoản</h2></div><span class="admin-badge admin-badge--active">QR VietQR</span></div>
            <div class="admin-bank-section__body admin-form-grid">
                <label class="admin-field"><span>Tên ngân hàng *</span><select name="bank_code" required><option value="">— Chọn ngân hàng —</option>@foreach($banks as $code => $name)<option value="{{ $code }}" @selected(old('bank_code', $account->bank_code) === $code)>{{ $name }} ({{ $code }})</option>@endforeach</select><small>Mã ngân hàng dùng để tạo QR đúng chuẩn SePay/VietQR.</small></label>
                <label class="admin-field"><span>Số tài khoản *</span><input name="account_number" value="{{ old('account_number', $account->account_number) }}" maxlength="100" autocomplete="off" required placeholder="Nhập số tài khoản"><small>Phải trùng chính xác với accountNumber SePay trả về.</small></label>
                <label class="admin-field"><span>Chủ tài khoản *</span><input name="account_holder" value="{{ old('account_holder', $account->account_holder) }}" maxlength="255" required placeholder="NGUYEN VAN A"></label>
                <label class="admin-field"><span>Chi nhánh</span><input name="branch" value="{{ old('branch', $account->branch) }}" maxlength="255" placeholder="Nhập chi nhánh ngân hàng"></label>
                <label class="admin-field"><span>Cú pháp nạp tiền *</span><input name="deposit_prefix" value="{{ old('deposit_prefix', $account->deposit_prefix) }}" maxlength="12" required placeholder="SKN"><small>ShopKey tự nối ID và chuỗi ngẫu nhiên để tạo nội dung duy nhất.</small></label>
                <label class="admin-field"><span>Số tiền nạp tối thiểu (VNĐ) *</span><input type="number" name="minimum_deposit" value="{{ old('minimum_deposit', $account->minimum_deposit) }}" min="1000" max="1000000000" required></label>
                <label class="admin-field"><span>Số tiền nạp tối đa (VNĐ)</span><input type="number" name="maximum_deposit" value="{{ old('maximum_deposit', $account->maximum_deposit) }}" min="1000" max="1000000000" placeholder="Để trống nếu không giới hạn"></label>
                <div class="admin-bank-switches">
                    <input type="hidden" name="is_visible" value="0"><label class="admin-toggle"><input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $account->is_visible))><span></span><b>Hiện tài khoản cho người dùng</b></label>
                </div>
                <label class="admin-field admin-field--full"><span>Ghi chú</span><textarea name="notes" rows="3" placeholder="Ghi chú nội bộ (nếu có)">{{ old('notes', $account->notes) }}</textarea></label>
            </div>
        </section>

        <section class="admin-panel admin-bank-section">
            <div class="admin-panel__head"><div><span class="admin-eyebrow">BƯỚC 2</span><h2>Cấu hình API SePay</h2></div><a href="https://developer.sepay.vn/vi/sepay-api/v2/tao-api-token" target="_blank" rel="noreferrer">Cách tạo token</a></div>
            <div class="admin-bank-section__body">
                <input type="hidden" name="provider" value="sepay">
                <div class="admin-bank-auto">
                    <input type="hidden" name="auto_confirm" value="0"><label class="admin-toggle"><input type="checkbox" name="auto_confirm" value="1" @checked(old('auto_confirm', $account->auto_confirm))><span></span><b>Tự động xác nhận và cộng tiền</b></label>
                    <small>Chỉ cộng ví khi chữ ký, tài khoản, mã hóa đơn và số tiền đều hợp lệ. Lệch tiền sẽ chuyển sang chờ duyệt.</small>
                </div>
                <div class="admin-form-grid">
                    <label class="admin-field"><span>Phương thức xác thực webhook *</span><select name="webhook_auth" required><option value="hmac" @selected(old('webhook_auth', $account->webhook_auth) === 'hmac')>HMAC-SHA256 (khuyên dùng)</option><option value="apikey" @selected(old('webhook_auth', $account->webhook_auth) === 'apikey')>API Key header</option></select><small>Phải chọn giống cấu hình webhook trên SePay.</small></label>
                    <label class="admin-field"><span>Access Token API v2</span><input type="password" name="api_token" autocomplete="new-password" maxlength="4096" placeholder="{{ $account->exists && $account->api_token ? 'Đã lưu an toàn — để trống nếu không đổi' : 'Nhập Bearer token SePay' }}"><small>Dùng cho truy vấn/đối soát API; token được mã hóa trước khi lưu.</small></label>
                    <label class="admin-field admin-field--full"><span>Webhook Secret / API Key *</span><input type="password" name="webhook_secret" autocomplete="new-password" minlength="12" maxlength="4096" placeholder="{{ $account->exists && $account->webhook_secret ? 'Đã lưu an toàn — để trống nếu không đổi' : 'Nhập khóa xác thực webhook từ SePay' }}"><small>HMAC: nhập Secret Key của webhook. API Key: nhập API key được gắn trong header Authorization. Không dùng lại mật khẩu tài khoản SePay.</small></label>
                </div>
            </div>
        </section>

        <div class="admin-bank-actions"><a href="{{ route('admin.bank-accounts.index') }}" class="admin-button">Hủy bỏ</a><button class="admin-button admin-button--primary"><x-icon name="check" :size="16" /> {{ $account->exists ? 'Lưu thay đổi' : 'Tạo tài khoản' }}</button></div>
    </form>
@endsection
