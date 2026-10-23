@extends('layouts.admin')

@section('title', 'Cài đặt chung')
@section('page_title', 'Cài đặt chung')

@section('content')
    <div class="admin-page-head">
        <div><span class="admin-eyebrow">HỆ THỐNG · NHẬN DIỆN</span><h1>Cài đặt chung</h1><p>Quản lý thông tin SEO, liên hệ và toàn bộ hình ảnh nhận diện của website.</p></div>
        <a href="{{ route('home') }}" target="_blank" class="admin-button"><x-icon name="home" :size="16" /> Xem website</a>
    </div>

    <form action="{{ route('admin.settings.general.update') }}" method="POST" enctype="multipart/form-data" class="admin-panel admin-settings-form">
        @csrf
        @method('PUT')

        <div class="admin-panel__head"><div><span class="admin-eyebrow">GENERAL SETTINGS</span><h2>Thông tin website</h2></div><span class="admin-badge admin-badge--active">Đang áp dụng</span></div>

        <div class="admin-settings-form__body">
            <div class="admin-form-grid admin-settings-fields">
                <label class="admin-field"><span>Tên trang web *</span><input name="site_name" value="{{ old('site_name', $setting->site_name) }}" maxlength="255" required placeholder="ShopKey - Digital Marketplace"><small>Được dùng trong tiêu đề, logo mặc định và chân trang.</small></label>
                <label class="admin-field"><span>Từ khóa website</span><input name="site_keywords" value="{{ old('site_keywords', $setting->site_keywords) }}" maxlength="3000" placeholder="key bản quyền, phần mềm, gift card..."><small>Phân cách các từ khóa bằng dấu phẩy.</small></label>
                <label class="admin-field admin-field--full"><span>Mô tả trang web</span><textarea name="site_description" rows="4" maxlength="2000" placeholder="Mô tả ngắn về website">{{ old('site_description', $setting->site_description) }}</textarea><small>Hiển thị trong thẻ meta description và phần giới thiệu ở chân trang.</small></label>
                <label class="admin-field"><span>Email liên hệ *</span><input type="email" name="contact_email" value="{{ old('contact_email', $setting->contact_email) }}" maxlength="255" required placeholder="support@shopkey.vn"></label>
                <label class="admin-field"><span>Tên nút CHAT AI *</span><input name="chat_label" value="{{ old('chat_label', $setting->chat_label) }}" maxlength="100" required placeholder="Hỗ trợ qua Chat AI"></label>
                <label class="admin-field admin-field--full"><span>Đường dẫn CHAT AI / hỗ trợ</span><input type="url" name="chat_url" value="{{ old('chat_url', $setting->chat_url) }}" maxlength="1000" placeholder="https://m.me/shopkey hoặc https://zalo.me/..."><small>Để trống thì nút hỗ trợ sẽ mở email liên hệ.</small></label>
            </div>

            <div class="admin-settings-divider"><span>Nội dung chân trang</span><small>Có thể dùng biến {site_name} và {year} trong nội dung.</small></div>

            @php
                $footerDefaults = App\Models\SiteSetting::defaults();
                $footerColumns = old('footer_columns', $setting->resolvedFooterColumns());
            @endphp
            <div class="admin-form-grid admin-footer-copy-fields">
                <label class="admin-field"><span>Đoạn giới thiệu footer *</span><textarea name="footer_description" rows="4" maxlength="1000" required>{{ old('footer_description', $setting->footer_description ?: $footerDefaults['footer_description']) }}</textarea><small>Hiển thị ngay dưới logo chân trang.</small></label>
                <label class="admin-field"><span>Dòng cam kết bảo vệ *</span><textarea name="footer_trust_text" rows="4" maxlength="255" required>{{ old('footer_trust_text', $setting->footer_trust_text ?: $footerDefaults['footer_trust_text']) }}</textarea><small>Ví dụ: Giao dịch được {site_name} bảo vệ.</small></label>
            </div>

            <div class="admin-footer-columns-editor">
                @foreach($footerColumns as $columnIndex => $column)
                    @php
                        $links = array_pad($column['links'] ?? [], 4, ['label' => '', 'url' => '']);
                    @endphp
                    <section class="admin-footer-column-card">
                        <div class="admin-footer-column-card__head"><span>0{{ $loop->iteration }}</span><strong>Cột liên kết {{ $loop->iteration }}</strong></div>
                        <label class="admin-field"><span>Tiêu đề cột *</span><input name="footer_columns[{{ $columnIndex }}][title]" value="{{ $column['title'] ?? '' }}" maxlength="100" required></label>
                        <div class="admin-footer-link-list">
                            @foreach($links as $linkIndex => $link)
                                <div><input name="footer_columns[{{ $columnIndex }}][links][{{ $linkIndex }}][label]" value="{{ $link['label'] ?? '' }}" maxlength="100" placeholder="Tên liên kết"><input name="footer_columns[{{ $columnIndex }}][links][{{ $linkIndex }}][url]" value="{{ $link['url'] ?? '' }}" maxlength="1000" placeholder="/duong-dan hoặc https://..."></div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="admin-form-grid admin-footer-bottom-fields">
                <label class="admin-field"><span>Dòng bản quyền *</span><input name="footer_copyright" value="{{ old('footer_copyright', $setting->footer_copyright ?: $footerDefaults['footer_copyright']) }}" maxlength="255" required><small>Hỗ trợ {year} và {site_name}.</small></label>
                <label class="admin-field"><span>Dòng thông tin bên phải *</span><input name="footer_bottom_text" value="{{ old('footer_bottom_text', $setting->footer_bottom_text ?: $footerDefaults['footer_bottom_text']) }}" maxlength="255" required><small>Ví dụ: Thanh toán an toàn · Giao tự động · Hỗ trợ 24/7.</small></label>
            </div>

            <div class="admin-settings-divider"><span>Hình ảnh nhận diện</span><small>Ảnh mới sẽ thay ảnh hiện tại ngay sau khi lưu.</small></div>

            <div class="site-assets-grid">
                @php
                    $assets = [
                        'logo' => ['label' => 'Logo chính trang web', 'path' => 'logo_path', 'hint' => 'PNG/WebP, nền trong suốt · tối đa 4MB', 'fallback' => asset('images/shopkey/favicon.svg')],
                        'dark_logo' => ['label' => 'Logo giao diện tối', 'path' => 'dark_logo_path', 'hint' => 'Dùng khi website bật dark mode · tối đa 4MB', 'fallback' => $setting->assetUrl('logo_path') ?: asset('images/shopkey/favicon.svg')],
                        'footer_logo' => ['label' => 'Logo chân trang', 'path' => 'footer_logo_path', 'hint' => 'Dùng trên nền tối · tối đa 4MB', 'fallback' => asset('images/shopkey/favicon.svg')],
                        'favicon' => ['label' => 'Favicon trang web', 'path' => 'favicon_path', 'hint' => 'PNG, WebP hoặc ICO · tối đa 2MB', 'fallback' => asset('images/shopkey/favicon.svg')],
                        'banner' => ['label' => 'Banner trang web', 'path' => 'banner_path', 'hint' => 'Khuyến nghị 1440 × 360px · tối đa 8MB', 'fallback' => null],
                        'cover' => ['label' => 'Ảnh bìa trang web', 'path' => 'cover_path', 'hint' => 'Ảnh hero trang chủ · tối đa 8MB', 'fallback' => asset('images/shopkey/marketplace-hero.png')],
                    ];
                @endphp

                @foreach($assets as $input => $asset)
                    @php($currentUrl = $setting->assetUrl($asset['path']) ?: $asset['fallback'])
                    <div @class(['site-asset-card', 'site-asset-card--wide' => $input === 'cover'])>
                        <div class="site-asset-card__head"><div><strong>{{ $asset['label'] }}</strong><small>{{ $asset['hint'] }}</small></div>@if($setting->getAttribute($asset['path']))<span class="admin-badge admin-badge--active">Tùy chỉnh</span>@else<span class="admin-badge">Mặc định</span>@endif</div>
                        <label class="site-upload-field" data-site-dropzone>
                            <input type="file" name="{{ $input }}" accept="{{ $input === 'favicon' ? '.png,.jpg,.jpeg,.webp,.ico' : 'image/png,image/jpeg,image/webp' }}" data-site-asset-input>
                            <span><x-icon name="plus" :size="21" /><b>Kéo thả hoặc click để tải ảnh lên</b><small data-site-file-name>Chưa chọn tệp mới</small></span>
                        </label>
                        <div class="site-asset-preview">
                            <small>Ảnh hiện tại</small>
                            @if($currentUrl)<img src="{{ $currentUrl }}" alt="{{ $asset['label'] }}" data-site-asset-preview>@else<div class="site-asset-preview__empty" data-site-preview-empty><x-icon name="box" :size="24" /> Chưa có ảnh</div><img src="" alt="{{ $asset['label'] }}" data-site-asset-preview hidden>@endif
                        </div>
                        @if($setting->getAttribute($asset['path']))
                            <label class="site-remove-asset"><input type="checkbox" name="remove_{{ $input }}" value="1"><span>Xóa ảnh tùy chỉnh và dùng lại mặc định</span></label>
                        @else
                            <input type="hidden" name="remove_{{ $input }}" value="0">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="admin-gateway-settings__footer"><div><x-icon name="shield" :size="17" /><span>Chỉ quản trị viên có quyền thay đổi; tệp được kiểm tra định dạng và kích thước trước khi lưu.</span></div><button class="admin-button admin-button--primary"><x-icon name="check" :size="16" /> Lưu thay đổi</button></div>
    </form>
@endsection
