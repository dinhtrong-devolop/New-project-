@extends('layouts.storefront')
@section('title', 'Ví ShopKey')
@section('content')
    <div class="page-hero page-hero--small"><div class="container"><nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('account.index') }}">Tài khoản</a><span>/</span><strong>Ví</strong></nav><h1>Ví ShopKey</h1><p>Số dư và sổ giao dịch minh bạch cho mọi thay đổi.</p></div></div>
    <section class="section"><div class="container wallet-layout">
        <aside class="wallet-balance-card"><span class="wallet-balance-card__icon"><x-icon name="wallet" :size="28" /></span><small>Số dư khả dụng</small><strong>{{ number_format($wallet->balance, 0, ',', '.') }}₫</strong><p>Đơn vị tiền tệ: {{ $wallet->currency }}</p><a href="{{ route('wallet.deposit.bank') }}" class="button button--light button--full"><x-icon name="bolt" :size="17" /> Nạp nhanh qua QR ngân hàng</a><a href="{{ route('wallet.deposit.card') }}" class="wallet-card-link">Nạp bằng thẻ cào</a><a href="{{ route('wallet.deposit.bank') }}">Xem hóa đơn ngân hàng</a></aside>
        <div class="customer-panel"><div class="customer-panel__head"><div><h2>Lịch sử giao dịch</h2><p>Mỗi thay đổi số dư được lưu thành một bút toán.</p></div><a href="{{ route('wallet.deposit.bank') }}" class="button button--primary"><x-icon name="plus" :size="16" /> Nạp tiền</a></div><div class="customer-table-wrap"><table class="customer-table"><thead><tr><th>Thời gian</th><th>Loại</th><th>Nội dung</th><th>Biến động</th><th>Số dư sau</th></tr></thead><tbody>
            @forelse($transactions as $transaction)<tr><td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td><td><span class="customer-status">{{ $transaction->type->label() }}</span></td><td>{{ $transaction->description }}</td><td><strong class="{{ $transaction->amount >= 0 ? 'money-positive' : 'money-negative' }}">{{ $transaction->amount >= 0 ? '+' : '' }}{{ number_format($transaction->amount, 0, ',', '.') }}₫</strong></td><td><strong>{{ number_format($transaction->balance_after, 0, ',', '.') }}₫</strong></td></tr>
            @empty<tr><td colspan="5" class="table-empty">Chưa có giao dịch ví.</td></tr>@endforelse
        </tbody></table></div>{{ $transactions->links() }}</div>
    </div></section>
@endsection
