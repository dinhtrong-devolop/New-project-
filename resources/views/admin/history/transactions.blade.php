@extends('layouts.admin')

@section('title', 'Lịch sử giao dịch')
@section('page_title', 'Lịch sử giao dịch')

@section('content')
    <div class="admin-page-head">
        <div><span class="admin-eyebrow">LỊCH SỬ TÀI CHÍNH</span><h1>Lịch sử giao dịch</h1><p>Đối soát toàn bộ biến động ví, số dư trước và sau mỗi giao dịch của thành viên.</p></div>
    </div>

    <section class="admin-panel">
        <form method="GET" class="admin-filterbar admin-filterbar--history">
            <div class="admin-search"><x-icon name="search" :size="17" /><input name="search" value="{{ request('search') }}" placeholder="ID, email hoặc nội dung giao dịch"></div>
            <select name="type"><option value="">Tất cả loại giao dịch</option>@foreach(App\Enums\WalletTransactionType::cases() as $type)<option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>@endforeach</select>
            <select name="status"><option value="">Tất cả trạng thái</option>@foreach(App\Enums\TransactionStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
            <button class="admin-button">Lọc lịch sử</button>
        </form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ID</th><th>Thành viên</th><th>Loại</th><th>Số tiền</th><th>Số dư trước</th><th>Số dư sau</th><th>Nội dung</th><th>Trạng thái</th><th>Thời gian</th></tr></thead><tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td><code>#{{ $transaction->id }}</code></td>
                    <td><div class="admin-user-cell admin-user-cell--compact"><span>{{ str($transaction->user->name)->substr(0, 2)->upper() }}</span><div><strong>{{ $transaction->user->name }}</strong><small>{{ $transaction->user->email }}</small></div></div></td>
                    <td><span class="admin-badge">{{ match($transaction->type) { App\Enums\WalletTransactionType::Deposit => 'Nạp tiền', App\Enums\WalletTransactionType::Purchase => 'Thanh toán', App\Enums\WalletTransactionType::Refund => 'Hoàn tiền', App\Enums\WalletTransactionType::AdminAdjustment => 'Điều chỉnh' } }}</span></td>
                    <td><strong @class(['admin-history-amount', 'is-credit' => $transaction->amount >= 0, 'is-debit' => $transaction->amount < 0])>{{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount, 0, ',', '.') }}₫</strong></td>
                    <td>{{ number_format($transaction->balance_before, 0, ',', '.') }}₫</td>
                    <td>{{ number_format($transaction->balance_after, 0, ',', '.') }}₫</td>
                    <td><span class="admin-history-description">{{ $transaction->description ?: '—' }}</span></td>
                    <td><span class="admin-badge admin-badge--{{ $transaction->status->value }}">{{ $transaction->status->label() }}</span></td>
                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="admin-empty">Chưa có giao dịch phù hợp với bộ lọc.</div></td></tr>
            @endforelse
        </tbody></table></div>
        <div class="admin-pagination">{{ $transactions->links() }}</div>
    </section>
@endsection
