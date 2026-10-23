@extends('layouts.admin')

@section('title', 'Lịch sử dùng mã giảm giá')
@section('page_title', 'Lịch sử dùng mã giảm giá')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">LỊCH SỬ MÃ GIẢM GIÁ</span><h1>Lịch sử dùng mã giảm giá</h1><p>Kiểm tra mã giảm giá đã sử dụng, thành viên áp dụng và số tiền giảm trên từng đơn hàng.</p></div></div>
    <section class="admin-panel">
        <form method="GET" class="admin-filterbar"><div class="admin-search"><x-icon name="search" :size="17" /><input name="search" value="{{ request('search') }}" placeholder="Mã giảm giá, mã đơn hoặc email"></div><button class="admin-button">Tìm trong lịch sử</button></form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ID</th><th>Mã giảm giá</th><th>Thành viên</th><th>Đơn hàng</th><th>Giá trị đơn</th><th>Số tiền giảm</th><th>Thời gian sử dụng</th><th></th></tr></thead><tbody>
            @forelse($usages as $usage)<tr><td><code>#{{ $usage->id }}</code></td><td><strong class="admin-voucher-code">{{ $usage->voucher->code }}</strong></td><td><div class="admin-user-cell admin-user-cell--compact"><span>{{ str($usage->user->name)->substr(0, 2)->upper() }}</span><div><strong>{{ $usage->user->name }}</strong><small>{{ $usage->user->email }}</small></div></div></td><td><strong>{{ $usage->order->order_code }}</strong></td><td>{{ number_format($usage->order->subtotal, 0, ',', '.') }}₫</td><td><strong class="admin-history-discount">-{{ number_format($usage->discount_amount, 0, ',', '.') }}₫</strong></td><td>{{ $usage->used_at->format('d/m/Y H:i') }}</td><td><a href="{{ route('admin.orders.show', $usage->order) }}" class="admin-table-link">Xem đơn</a></td></tr>@empty<tr><td colspan="8"><div class="admin-empty">Chưa có lượt sử dụng mã giảm giá phù hợp.</div></td></tr>@endforelse
        </tbody></table></div><div class="admin-pagination">{{ $usages->links() }}</div>
    </section>
@endsection
