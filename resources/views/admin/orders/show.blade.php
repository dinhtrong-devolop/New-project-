@extends('layouts.admin')

@section('title', $order->order_code)
@section('page_title', 'Chi tiết đơn hàng')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">CHI TIẾT ĐƠN HÀNG</span><h1>{{ $order->order_code }}</h1><p>Tạo lúc {{ $order->created_at->format('H:i, d/m/Y') }} bởi {{ $order->user->email }}.</p></div><a href="{{ route('admin.orders.index') }}" class="admin-button">Quay lại</a></div>
    <section class="admin-order-summary"><div><small>Tạm tính</small><strong>{{ number_format($order->subtotal, 0, ',', '.') }}₫</strong></div><div><small>Giảm giá</small><strong>-{{ number_format($order->discount_amount, 0, ',', '.') }}₫</strong></div><div><small>Tổng thanh toán</small><strong>{{ number_format($order->total, 0, ',', '.') }}₫</strong></div><div><small>Phương thức</small><strong>{{ $order->payment_method }}</strong></div></section>
    @if($order->refundRequest)
        <section class="admin-panel admin-form-card admin-refund-request">
            <div class="admin-form-card__head"><span class="admin-eyebrow">YÊU CẦU HOÀN TIỀN</span><h2>Khách hàng yêu cầu hoàn tiền</h2><p>Trạng thái: {{ match($order->refundRequest->status) { 'pending' => 'Đang chờ xử lý', 'approved' => 'Đã duyệt', default => 'Đã từ chối' } }}</p></div>
            <p><strong>Lý do:</strong> {{ $order->refundRequest->reason }}</p>
            @if($order->refundRequest->admin_note)<p><strong>Phản hồi quản trị:</strong> {{ $order->refundRequest->admin_note }}</p>@endif
            @if($order->refundRequest->status === 'pending')
                <form action="{{ route('admin.orders.refund-request', $order) }}" method="POST" class="admin-refund-request__form">@csrf @method('PATCH')
                    <label class="admin-field"><span>Phản hồi quản trị</span><textarea name="admin_note" rows="3" maxlength="2000"></textarea></label>
                    <div><button name="action" value="reject" class="admin-button admin-button--danger">Từ chối</button><button name="action" value="approve" class="admin-button admin-button--primary">Duyệt và hoàn tiền</button></div>
                </form>
            @endif
        </section>
    @endif
    <div class="admin-dashboard-grid">
        <section class="admin-panel"><div class="admin-panel__head"><div><span class="admin-eyebrow">SẢN PHẨM TRONG ĐƠN</span><h2>Sản phẩm trong đơn</h2></div></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th><th>Giao hàng</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td><strong>{{ $item->product_name }}</strong><small class="admin-table-sub">{{ $item->product_sku }}</small></td><td>{{ number_format($item->unit_price, 0, ',', '.') }}₫</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->subtotal, 0, ',', '.') }}₫</td><td><span class="admin-badge admin-badge--{{ $item->delivery_status->value }}">{{ $item->delivery_status->label() }}</span></td></tr>@endforeach</tbody></table></div><div class="admin-security-note"><x-icon name="shield" :size="20" /><div><strong>Dữ liệu bàn giao được bảo vệ</strong><p>Quản trị viên chỉ thấy trạng thái và mã kho; thông tin đăng nhập đã bán không được hiển thị trực tiếp trong giao diện.</p></div></div></section>
        <aside class="admin-panel admin-form-card"><div class="admin-form-card__head"><span class="admin-eyebrow">QUẢN LÝ TRẠNG THÁI</span><h2>Cập nhật trạng thái</h2><p>Thao tác này được ghi vào nhật ký hoạt động.</p></div><form action="{{ route('admin.orders.update', $order) }}" method="POST" class="admin-form-grid admin-form-grid--one">@csrf @method('PATCH')<label class="admin-field"><span>Trạng thái đơn</span><select name="order_status">@foreach(App\Enums\OrderStatus::cases() as $status)<option value="{{ $status->value }}" @selected($order->order_status === $status)>{{ $status->label() }}</option>@endforeach</select></label><label class="admin-field"><span>Thanh toán</span><select name="payment_status">@foreach(App\Enums\PaymentStatus::cases() as $status)<option value="{{ $status->value }}" @selected($order->payment_status === $status)>{{ $status->label() }}</option>@endforeach</select></label><label class="admin-field"><span>Ghi chú quản trị</span><textarea name="admin_note" rows="4"></textarea></label><button class="admin-button admin-button--primary admin-button--wide">Lưu trạng thái</button></form></aside>
    </div>
@endsection
