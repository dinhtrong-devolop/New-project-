@extends('layouts.admin')

@section('title', 'Lịch sử nạp thẻ cào')
@section('page_title', 'Lịch sử nạp thẻ cào')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">LỊCH SỬ NẠP THẺ CÀO</span><h1>Lịch sử nạp thẻ cào</h1><p>Theo dõi các yêu cầu nạp thẻ, số tiền thực nhận và phản hồi từ nhà cung cấp.</p></div></div>
    <section class="admin-panel">
        <form method="GET" class="admin-filterbar"><div class="admin-search"><x-icon name="search" :size="17" /><input name="search" value="{{ request('search') }}" placeholder="Mã yêu cầu, giao dịch hoặc email"></div><select name="status"><option value="">Tất cả trạng thái</option>@foreach(App\Enums\CardDepositStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select><button class="admin-button">Lọc lịch sử</button></form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã yêu cầu</th><th>Khách hàng</th><th>Nhà mạng</th><th>Số sê-ri</th><th>Mệnh giá</th><th>Thực nhận</th><th>Trạng thái</th><th>Thời gian</th></tr></thead><tbody>
            @forelse($deposits as $deposit)<tr><td><code>{{ $deposit->request_id }}</code><small class="admin-table-sub">{{ $deposit->provider_transaction_id ?: 'Chưa có mã đối soát' }}</small></td><td>{{ $deposit->user->email }}</td><td>{{ $deposit->provider }}</td><td>••••{{ $deposit->serial_last_four }}</td><td>{{ number_format($deposit->declared_amount, 0, ',', '.') }}₫</td><td>{{ $deposit->received_amount === null ? '—' : number_format($deposit->received_amount, 0, ',', '.').'₫' }}</td><td><span class="admin-badge admin-badge--{{ $deposit->status->value }}">{{ $deposit->status->label() }}</span></td><td>{{ $deposit->created_at->format('d/m/Y H:i') }}</td></tr>@empty<tr><td colspan="8"><div class="admin-empty">Chưa có lịch sử nạp thẻ cào phù hợp.</div></td></tr>@endforelse
        </tbody></table></div><div class="admin-pagination">{{ $deposits->links() }}</div>
        <div class="admin-security-note"><x-icon name="shield" :size="20" /><div><strong>Dữ liệu thẻ được bảo vệ</strong><p>Trang lịch sử chỉ hiển thị bốn số cuối serial, không hiển thị mã thẻ hoặc dữ liệu nhạy cảm.</p></div></div>
    </section>
@endsection
