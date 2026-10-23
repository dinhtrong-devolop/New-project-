@extends('layouts.storefront')
@section('title', 'Lịch sử nạp thẻ - ShopKey')
@section('content')
    <div class="page-hero page-hero--small"><div class="container"><nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('wallet.index') }}">Ví</a><span>/</span><strong>Lịch sử nạp</strong></nav><h1>Lịch sử nạp thẻ</h1><p>Theo dõi trạng thái xử lý và số tiền thực nhận từ đối tác.</p></div></div>
    <section class="section"><div class="container customer-panel">
        @if(session('status'))<div class="form-alert form-alert--success storefront-alert"><x-icon name="check" :size="18" /> {{ session('status') }}</div>@endif
        <div class="customer-panel__head"><div><h2>Yêu cầu nạp tiền</h2><p>Mỗi yêu cầu có một mã duy nhất để đối soát.</p></div><a href="{{ route('wallet.deposit.card') }}" class="button button--primary"><x-icon name="plus" :size="17" /> Nạp thẻ mới</a></div>
        <div class="customer-table-wrap"><table class="customer-table"><thead><tr><th>Mã yêu cầu</th><th>Nhà mạng</th><th>Khai báo</th><th>Thực tế</th><th>Thực nhận</th><th>Trạng thái</th><th>Thời gian</th></tr></thead><tbody>
            @forelse($deposits as $deposit)<tr><td><strong>{{ $deposit->request_id }}</strong><small class="table-subline">Sê-ri ****{{ $deposit->serial_last_four }}</small></td><td>{{ $deposit->provider }}</td><td>{{ number_format($deposit->declared_amount, 0, ',', '.') }}₫</td><td>{{ $deposit->actual_amount ? number_format($deposit->actual_amount, 0, ',', '.').'₫' : '—' }}</td><td>{{ $deposit->received_amount ? number_format($deposit->received_amount, 0, ',', '.').'₫' : '—' }}</td><td><span class="customer-status customer-status--{{ $deposit->status->value }}">{{ $deposit->status->label() }}</span>@if($deposit->provider_message)<small class="table-subline">{{ $deposit->provider_message }}</small>@endif</td><td>{{ $deposit->created_at->format('d/m/Y H:i') }}</td></tr>
            @empty<tr><td colspan="7" class="table-empty">Chưa có yêu cầu nạp thẻ.</td></tr>@endforelse
        </tbody></table></div>{{ $deposits->links() }}
    </div></section>
@endsection
