@extends('layouts.storefront')
@section('title', 'Đơn hàng của tôi - ShopKey')
@section('content')
    <div class="page-hero page-hero--small"><div class="container"><nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('account.index') }}">Tài khoản</a><span>/</span><strong>Đơn hàng</strong></nav><h1>Đơn hàng của tôi</h1><p>Tra cứu thanh toán và sản phẩm số đã được bàn giao.</p></div></div>
    <section class="section"><div class="container customer-panel">
        <div class="customer-panel__head"><div><h2>Lịch sử mua hàng</h2><p>{{ $orders->total() }} đơn hàng trong tài khoản</p></div><a href="{{ route('products.index') }}" class="button button--primary">Tiếp tục mua sắm</a></div>
        <div class="customer-table-wrap"><table class="customer-table"><thead><tr><th>Mã đơn</th><th>Thời gian</th><th>Sản phẩm</th><th>Thanh toán</th><th>Trạng thái</th><th></th></tr></thead><tbody>
            @forelse($orders as $order)<tr><td><strong>{{ $order->order_code }}</strong></td><td>{{ $order->created_at->format('d/m/Y H:i') }}</td><td>{{ $order->items_count }}</td><td><strong>{{ number_format($order->total, 0, ',', '.') }}₫</strong></td><td><span class="customer-status customer-status--{{ $order->order_status->value }}">{{ $order->order_status->label() }}</span></td><td><a href="{{ route('orders.show', $order) }}">Xem chi tiết →</a></td></tr>
            @empty<tr><td colspan="6" class="table-empty">Bạn chưa có đơn hàng.</td></tr>@endforelse
        </tbody></table></div>{{ $orders->links() }}
    </div></section>
@endsection
