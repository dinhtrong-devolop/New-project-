@extends('layouts.admin')

@section('title', 'Đơn hàng')
@section('page_title', 'Quản lý đơn hàng')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">QUẢN LÝ ĐƠN HÀNG</span><h1>Đơn hàng</h1><p>Tìm kiếm, theo dõi thanh toán và trạng thái giao sản phẩm số.</p></div></div>
    <section class="admin-panel"><form method="GET" class="admin-filterbar"><div class="admin-search"><x-icon name="search" :size="17" /><input name="search" value="{{ request('search') }}" placeholder="Mã đơn hoặc email"></div><select name="status"><option value="">Tất cả trạng thái</option>@foreach(App\Enums\OrderStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select><button class="admin-button">Lọc đơn</button></form><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Sản phẩm</th><th>Tổng tiền</th><th>Thanh toán</th><th>Đơn hàng</th><th>Ngày tạo</th><th></th></tr></thead><tbody>
        @forelse($orders as $order)<tr><td><strong>{{ $order->order_code }}</strong></td><td>{{ $order->user->email }}</td><td>{{ $order->items_count }}</td><td><strong>{{ number_format($order->total, 0, ',', '.') }}₫</strong></td><td><span class="admin-badge admin-badge--{{ $order->payment_status->value }}">{{ $order->payment_status->label() }}</span></td><td><span class="admin-badge admin-badge--{{ $order->order_status->value }}">{{ $order->order_status->label() }}</span></td><td>{{ $order->created_at->format('d/m/Y H:i') }}</td><td><a href="{{ route('admin.orders.show', $order) }}" class="admin-table-link">Mở đơn</a></td></tr>@empty<tr><td colspan="8"><div class="admin-empty">Chưa có đơn hàng phù hợp.</div></td></tr>@endforelse
    </tbody></table></div><div class="admin-pagination">{{ $orders->links() }}</div></section>
@endsection
