@extends('layouts.admin')

@section('title', 'Tài khoản ngân hàng')
@section('page_title', 'Ngân hàng')

@section('content')
    <div class="admin-page-head">
        <div><span class="admin-eyebrow">TÀI CHÍNH · SEPAY</span><h1>Quản lý tài khoản ngân hàng</h1><p>Tài khoản nhận tiền, QR và cấu hình xác nhận giao dịch tự động.</p></div>
        <a href="{{ route('admin.bank-accounts.create') }}" class="admin-button admin-button--primary"><x-icon name="plus" :size="16" /> Thêm tài khoản mới</a>
    </div>

    <div class="admin-provider-notice">
        <span><x-icon name="shield" :size="21" /></span>
        <div><strong>Tích hợp chính thức SePay.vn</strong><p>Mỗi tài khoản có webhook URL và khóa xác thực riêng. Token, API key và HMAC secret không bao giờ hiển thị lại sau khi lưu.</p></div>
        <a href="https://developer.sepay.vn/vi" target="_blank" rel="noreferrer">Xem tài liệu <x-icon name="arrow-right" :size="15" /></a>
    </div>

    <section class="admin-panel">
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ID</th><th>Ngân hàng</th><th>Số tài khoản</th><th>Chủ tài khoản</th><th>Giới hạn</th><th>Hóa đơn</th><th>Hiển thị</th><th>Tự động</th><th>Hành động</th></tr></thead><tbody>
            @forelse($accounts as $account)
                <tr>
                    <td><code>#{{ $account->id }}</code></td>
                    <td><strong>{{ $account->bank_name }}</strong><small class="admin-table-sub">{{ $account->bank_code }} · {{ strtoupper($account->provider) }}</small></td>
                    <td><code>{{ $account->account_number }}</code><small class="admin-table-sub">Tiền tố: {{ $account->deposit_prefix }}</small></td>
                    <td>{{ $account->account_holder }}<small class="admin-table-sub">{{ $account->branch ?: 'Không ghi chi nhánh' }}</small></td>
                    <td>{{ number_format($account->minimum_deposit, 0, ',', '.') }}₫<small class="admin-table-sub">Tối đa: {{ $account->maximum_deposit ? number_format($account->maximum_deposit, 0, ',', '.').'₫' : 'Không giới hạn' }}</small></td>
                    <td>{{ $account->deposits_count }}<small class="admin-table-sub">Đã cộng {{ number_format($account->paid_total ?? 0, 0, ',', '.') }}₫</small></td>
                    <td><span class="admin-badge admin-badge--{{ $account->is_visible ? 'active' : 'disabled' }}">{{ $account->is_visible ? 'Đang hiện' : 'Đã ẩn' }}</span></td>
                    <td><span class="admin-badge admin-badge--{{ $account->auto_confirm ? 'active' : 'disabled' }}">{{ $account->auto_confirm ? 'Đang bật' : 'Đang tắt' }}</span></td>
                    <td><div class="admin-row-actions"><a href="{{ route('admin.bank-accounts.edit', $account) }}">Cấu hình</a><form action="{{ route('admin.bank-accounts.toggle', $account) }}" method="POST">@csrf @method('PATCH')<button class="admin-table-link">{{ $account->is_visible ? 'Ẩn' : 'Hiện' }}</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="admin-empty">Chưa có tài khoản ngân hàng. Hãy thêm tài khoản đầu tiên để nhận chuyển khoản.</div></td></tr>
            @endforelse
        </tbody></table></div>
        <div class="admin-pagination">{{ $accounts->links() }}</div>
    </section>
@endsection
