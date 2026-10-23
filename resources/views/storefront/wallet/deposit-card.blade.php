@extends('layouts.storefront')
@section('title', 'Nạp thẻ cào - ShopKey')
@section('content')
    <div class="page-hero page-hero--small"><div class="container"><nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('wallet.index') }}">Ví</a><span>/</span><strong>Nạp thẻ</strong></nav><h1>Nạp tiền bằng thẻ cào</h1><p>Yêu cầu được gửi bảo mật tới Naptudong và chỉ cộng ví sau callback hợp lệ.</p></div></div>
    <section class="section"><div class="container deposit-layout">
        <div class="deposit-form-card">
            <div class="deposit-form-card__head"><span><x-icon name="plus" :size="24" /></span><div><h2>Thông tin thẻ</h2><p>Kiểm tra đúng nhà mạng và mệnh giá trước khi gửi.</p></div></div>
            @if(!$configured)<div class="form-alert form-alert--warning"><x-icon name="shield" :size="18" /><div><strong>Cổng nạp thẻ chưa sẵn sàng</strong><p>Quản trị viên cần bật và nhập đủ thông tin trong mục Cài đặt nạp thẻ.</p></div></div>@endif
            @if($errors->any())<div class="form-alert form-alert--error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
            <form action="{{ route('wallet.deposit.card.store') }}" method="POST" class="deposit-form">@csrf
                <div class="form-field"><label for="provider">Nhà mạng</label><div class="form-control"><select id="provider" name="provider" required><option value="">Chọn nhà mạng</option>@foreach($providers as $provider)<option value="{{ $provider }}" @selected(old('provider') === $provider)>{{ $provider }}</option>@endforeach</select></div></div>
                <div class="form-field"><label for="amount">Mệnh giá khai báo</label><div class="form-control"><select id="amount" name="amount" required><option value="">Chọn mệnh giá</option>@foreach($denominations as $amount)<option value="{{ $amount }}" @selected((int)old('amount') === $amount)>{{ number_format($amount, 0, ',', '.') }}₫</option>@endforeach</select></div></div>
                <div class="form-field"><label for="serial">Số serial</label><div class="form-control"><input id="serial" name="serial" value="{{ old('serial') }}" minlength="8" maxlength="32" autocomplete="off" required></div></div>
                <div class="form-field"><label for="code">Mã thẻ</label><div class="form-control"><input id="code" name="code" value="" minlength="8" maxlength="32" autocomplete="off" required></div><small>Mã thẻ được mã hóa tạm thời và xóa sau khi xử lý.</small></div>
                <button class="button button--primary button--large button--full" @disabled(!$configured)><x-icon name="lock" :size="17" /> Gửi thẻ an toàn</button>
            </form>
        </div>
        <aside class="deposit-guide"><h2>Quy trình xử lý</h2>@if($discountPercent > 0)<div class="deposit-rate"><span>Chiết khấu dự kiến</span><strong>{{ rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.') }}%</strong></div>@endif<ol><li><b>1</b><span><strong>Gửi yêu cầu</strong><small>ShopKey sinh mã SK duy nhất.</small></span></li><li><b>2</b><span><strong>Đối tác kiểm tra</strong><small>Trạng thái ban đầu là pending.</small></span></li><li><b>3</b><span><strong>Xác minh callback</strong><small>Chữ ký phải khớp khóa đối tác.</small></span></li><li><b>4</b><span><strong>Cộng ví một lần</strong><small>Idempotency ngăn callback trùng.</small></span></li></ol><div class="deposit-warning"><x-icon name="shield" :size="20" /><p>Số tiền cộng ví là số thực nhận do đối tác xác nhận, có thể khác mệnh giá khai báo.</p></div><a href="{{ route('wallet.deposits') }}">Xem lịch sử nạp thẻ →</a></aside>
    </div></section>
@endsection
