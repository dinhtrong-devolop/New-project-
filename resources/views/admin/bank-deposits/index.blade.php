@extends('layouts.admin')

@section('title', 'Hóa đơn ngân hàng')
@section('page_title', 'Hóa đơn ngân hàng')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">TÀI CHÍNH · ĐỐI SOÁT</span><h1>Quản lý hóa đơn ngân hàng</h1><p>Theo dõi hóa đơn QR, giao dịch SePay và các trường hợp cần duyệt thủ công.</p></div><a href="{{ route('admin.bank-accounts.index') }}" class="admin-button"><x-icon name="wallet" :size="16" /> Tài khoản nhận tiền</a></div>

    <div class="admin-stat-grid admin-bank-stats">
        <article><span class="admin-stat-icon admin-stat-icon--teal"><x-icon name="orders" :size="22" /></span><small>TỔNG HÓA ĐƠN</small><strong>{{ number_format($stats['total']) }}</strong><em>Theo bộ lọc toàn hệ thống</em></article>
        <article><span class="admin-stat-icon admin-stat-icon--amber"><x-icon name="clock" :size="22" /></span><small>CHỜ XỬ LÝ</small><strong>{{ number_format($stats['pending']) }}</strong><em>Pending và cần kiểm tra</em></article>
        <article><span class="admin-stat-icon admin-stat-icon--blue"><x-icon name="check" :size="22" /></span><small>ĐÃ CỘNG TIỀN</small><strong>{{ number_format($stats['paid']) }}</strong><em>Giao dịch hoàn tất</em></article>
        <article><span class="admin-stat-icon admin-stat-icon--violet"><x-icon name="bolt" :size="22" /></span><small>TỔNG TIỀN ĐÃ CỘNG</small><strong>{{ number_format($stats['paid_amount'], 0, ',', '.') }}₫</strong><em>Giá trị nhận thực tế</em></article>
    </div>

    <section class="admin-panel">
        <form method="GET" class="admin-filterbar"><div class="admin-search"><x-icon name="search" :size="17" /><input name="search" value="{{ request('search') }}" placeholder="Mã hóa đơn, email, mã tham chiếu"></div><select name="status"><option value="">Tất cả trạng thái</option>@foreach(App\Enums\BankDepositStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select><select name="bank_account_id"><option value="">Tất cả tài khoản</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected((string)request('bank_account_id') === (string)$account->id)>{{ $account->bank_name }} · {{ $account->account_number }}</option>@endforeach</select><button class="admin-button">Lọc hóa đơn</button></form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã hóa đơn</th><th>Thành viên</th><th>Tài khoản nhận</th><th>Số tiền</th><th>Nhận thực tế</th><th>Trạng thái</th><th>SePay</th><th>Thời gian</th><th>Hành động</th></tr></thead><tbody>
            @forelse($deposits as $deposit)
                <tr>
                    <td><code>{{ $deposit->invoice_code }}</code><small class="admin-table-sub">#{{ $deposit->id }}</small></td>
                    <td>{{ $deposit->user->email }}<small class="admin-table-sub">{{ $deposit->user->name }}</small></td>
                    <td>{{ $deposit->bankAccount->bank_name }}<small class="admin-table-sub">{{ $deposit->bankAccount->account_number }}</small></td>
                    <td><strong>{{ number_format($deposit->amount, 0, ',', '.') }}₫</strong></td>
                    <td>{{ $deposit->received_amount === null ? '—' : number_format($deposit->received_amount, 0, ',', '.').'₫' }}</td>
                    <td><span class="admin-badge admin-badge--{{ $deposit->status->value }}">{{ $deposit->status->label() }}</span></td>
                    <td><code>{{ $deposit->sepay_transaction_id ?: '—' }}</code><small class="admin-table-sub">{{ $deposit->reference_code ?: 'Chưa có tham chiếu' }}</small></td>
                    <td>{{ $deposit->created_at->format('d/m/Y H:i') }}<small class="admin-table-sub">{{ $deposit->paid_at ? 'Đã trả '.$deposit->paid_at->format('d/m H:i') : optional($deposit->expires_at)->diffForHumans() }}</small></td>
                    <td>@if($deposit->status === App\Enums\BankDepositStatus::Review)<form action="{{ route('admin.bank-deposits.approve', $deposit) }}" method="POST" class="admin-inline-approve" onsubmit="return confirm('Duyệt và cộng số tiền thực nhận vào ví khách hàng?')">@csrf<input name="note" value="Đã đối soát thủ công giao dịch SePay" required><button class="admin-table-link">Duyệt cộng tiền</button></form>@else<span class="admin-table-sub">Không có</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="admin-empty">Không tìm thấy hóa đơn ngân hàng phù hợp bộ lọc.</div></td></tr>
            @endforelse
        </tbody></table></div><div class="admin-pagination">{{ $deposits->links() }}</div>
    </section>
@endsection
