@extends('layouts.admin')

@section('title', 'Lịch sử đặt dịch vụ')
@section('page_title', 'Lịch sử đặt dịch vụ')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">LỊCH SỬ ĐƠN HÀNG</span><h1>Lịch sử đặt dịch vụ</h1><p>Tra cứu các đơn sản phẩm và dịch vụ số đã được khách hàng khởi tạo trên ShopKey.</p></div></div>
    <section class="admin-panel">
        <form method="GET" class="admin-filterbar"><div class="admin-search"><x-icon name="search" :size="17" /><input name="search" value="{{ request('search') }}" placeholder="Mã đơn, tên hoặc email khách hàng"></div><select name="status"><option value="">Tất cả trạng thái</option>@foreach(App\Enums\OrderStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select><button class="admin-button">Lọc lịch sử</button></form>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Sản phẩm</th><th>Tạm tính</th><th>Giảm giá</th><th>Thanh toán</th><th>Đơn hàng</th><th>Thời gian</th><th></th></tr></thead><tbody>
            @forelse($orders as $order)<tr><td><strong>{{ $order->order_code }}</strong></td><td>{{ $order->user->email }}</td><td>{{ $order->items_count }}</td><td>{{ number_format($order->subtotal, 0, ',', '.') }}₫</td><td>{{ number_format($order->discount_amount, 0, ',', '.') }}₫</td><td><strong>{{ number_format($order->total, 0, ',', '.') }}₫</strong><small class="admin-table-sub"><span class="admin-badge admin-badge--{{ $order->payment_status->value }}">{{ $order->payment_status->label() }}</span></small></td><td><span class="admin-badge admin-badge--{{ $order->order_status->value }}">{{ $order->order_status->label() }}</span></td><td>{{ $order->created_at->format('d/m/Y H:i') }}</td><td><a href="{{ route('admin.orders.show', $order) }}" class="admin-table-link">Mở đơn</a></td></tr>@empty<tr><td colspan="9"><div class="admin-empty">Chưa có lịch sử đặt dịch vụ phù hợp.</div></td></tr>@endforelse
        </tbody></table></div><div class="admin-pagination">{{ $orders->links() }}</div>
    </section>
@endsection
