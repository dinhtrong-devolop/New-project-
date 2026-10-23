@extends('layouts.admin')

@section('title', 'Voucher')
@section('page_title', 'Quản lý voucher')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">PROMOTIONS</span><h1>Voucher giảm giá</h1><p>Tạo mã giảm giá với giới hạn thời gian và lượt sử dụng rõ ràng.</p></div></div>
    <div class="admin-split-layout">
        <section class="admin-panel"><div class="admin-panel__head"><div><span class="admin-eyebrow">MÃ HIỆN CÓ</span><h2>{{ $vouchers->total() }} voucher</h2></div></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã</th><th>Giảm</th><th>Đơn tối thiểu</th><th>Đã dùng</th><th>Hết hạn</th><th>Trạng thái</th><th></th></tr></thead><tbody>
            @forelse($vouchers as $voucher)<tr><td><code class="admin-voucher-code">{{ $voucher->code }}</code></td><td><strong>{{ $voucher->type->value === 'percentage' ? $voucher->discount_value.'%' : number_format($voucher->discount_value, 0, ',', '.').'₫' }}</strong></td><td>{{ number_format($voucher->minimum_order_amount, 0, ',', '.') }}₫</td><td>{{ $voucher->used_count }}/{{ $voucher->usage_limit ?? '∞' }}</td><td>{{ $voucher->expires_at?->format('d/m/Y') ?? 'Không hạn' }}</td><td><span class="admin-badge {{ $voucher->is_active ? 'admin-badge--active' : 'admin-badge--inactive' }}">{{ $voucher->is_active ? 'Hoạt động' : 'Ngừng hoạt động' }}</span></td><td><form action="{{ route('admin.vouchers.toggle', $voucher) }}" method="POST">@csrf @method('PATCH')<button class="admin-table-link">{{ $voucher->is_active ? 'Tắt' : 'Bật' }}</button></form></td></tr>@empty<tr><td colspan="7"><div class="admin-empty">Chưa có voucher.</div></td></tr>@endforelse
        </tbody></table></div><div class="admin-pagination">{{ $vouchers->links() }}</div></section>
        <aside class="admin-panel admin-form-card"><div class="admin-form-card__head"><span class="admin-eyebrow">TẠO MÃ</span><h2>Voucher mới</h2><p>Giá trị tiền tệ sử dụng đơn vị VND.</p></div><form action="{{ route('admin.vouchers.store') }}" method="POST" class="admin-form-grid admin-form-grid--one">@csrf
            <label class="admin-field"><span>Mã voucher</span><input name="code" value="{{ old('code') }}" placeholder="SHOPKEY20" required></label>
            <label class="admin-field"><span>Loại giảm giá</span><select name="type"><option value="percentage">Phần trăm</option><option value="fixed_amount">Số tiền cố định</option></select></label>
            <label class="admin-field"><span>Giá trị giảm</span><input type="number" min="1" name="discount_value" value="{{ old('discount_value') }}" required></label>
            <label class="admin-field"><span>Đơn hàng tối thiểu</span><input type="number" min="0" name="minimum_order_amount" value="{{ old('minimum_order_amount', 0) }}" required></label>
            <label class="admin-field"><span>Giảm tối đa</span><input type="number" min="0" name="maximum_discount" value="{{ old('maximum_discount') }}"></label>
            <div class="admin-form-grid"><label class="admin-field"><span>Bắt đầu</span><input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"></label><label class="admin-field"><span>Hết hạn</span><input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"></label></div>
            <div class="admin-form-grid"><label class="admin-field"><span>Giới hạn tổng</span><input type="number" min="1" name="usage_limit" value="{{ old('usage_limit') }}"></label><label class="admin-field"><span>Mỗi người</span><input type="number" min="1" name="per_user_usage_limit" value="{{ old('per_user_usage_limit', 1) }}"></label></div>
            <label class="admin-toggle"><input type="checkbox" name="is_active" value="1" checked><span></span><b>Kích hoạt ngay</b></label>
            <button class="admin-button admin-button--primary admin-button--wide"><x-icon name="plus" :size="17" /> Tạo voucher</button>
        </form></aside>
    </div>
@endsection
