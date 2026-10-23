@extends('layouts.storefront')

@section('title', 'Nạp tiền qua ngân hàng')

@section('content')
    <div class="page-hero page-hero--small"><div class="container"><nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('wallet.index') }}">Ví</a><span>/</span><strong>Nạp qua ngân hàng</strong></nav><h1>Nạp tiền tự động qua QR</h1><p>Chuyển khoản đúng số tiền và nội dung, SePay sẽ xác nhận để ShopKey cộng ví tự động.</p></div></div>

    <section class="section"><div class="container bank-deposit-layout">
        <div class="deposit-form-card bank-deposit-create">
            <div class="deposit-form-card__head"><span><x-icon name="wallet" :size="24" /></span><div><h2>Tạo mã QR chuyển khoản</h2><p>Mỗi hóa đơn có nội dung nhận diện duy nhất.</p></div></div>

            @if($accounts->isEmpty())
                <div class="form-alert form-alert--warning">Hiện chưa có tài khoản ngân hàng nhận tiền. Vui lòng quay lại sau hoặc liên hệ hỗ trợ.</div>
            @else
                <form action="{{ route('wallet.deposit.bank.store') }}" method="POST" class="bank-deposit-form">@csrf
                    <fieldset class="bank-account-picker"><legend>Chọn ngân hàng nhận tiền</legend>
                        @foreach($accounts as $account)
                            <label><input type="radio" name="bank_account_id" value="{{ $account->id }}" @checked((string)old('bank_account_id', $loop->first ? $account->id : '') === (string)$account->id)><span><b>{{ $account->bank_name }}</b><small>{{ $account->account_number }} · {{ $account->account_holder }}</small><em>Từ {{ number_format($account->minimum_deposit, 0, ',', '.') }}₫</em></span></label>
                        @endforeach
                    </fieldset>
                    <label class="form-field"><span>Số tiền muốn nạp</span><div class="form-control"><x-icon name="wallet" :size="18" /><input type="number" name="amount" value="{{ old('amount', 100000) }}" min="1000" max="1000000000" step="1000" required placeholder="100.000"></div><small>Nhập số tiền VNĐ, tối thiểu theo tài khoản đã chọn.</small></label>
                    <div class="bank-quick-amounts" data-bank-quick-amounts>@foreach([50000, 100000, 200000, 500000, 1000000] as $amount)<button type="button" data-amount="{{ $amount }}">{{ number_format($amount, 0, ',', '.') }}₫</button>@endforeach</div>
                    <button class="button button--primary button--full"><x-icon name="plus" :size="17" /> Tạo hóa đơn và mã QR</button>
                </form>
            @endif
        </div>

        <aside class="deposit-guide bank-auto-guide"><h2>Nạp tiền an toàn</h2><ol><li><b>1</b><span><strong>Tạo hóa đơn</strong><small>Chọn tài khoản và số tiền cần nạp.</small></span></li><li><b>2</b><span><strong>Quét QR</strong><small>Không sửa số tiền hoặc nội dung.</small></span></li><li><b>3</b><span><strong>SePay xác nhận</strong><small>Webhook được kiểm tra chữ ký và đối soát.</small></span></li><li><b>4</b><span><strong>Cộng ví tự động</strong><small>ShopKey chỉ cộng đúng một lần.</small></span></li></ol><div class="deposit-warning"><x-icon name="shield" :size="20" /><p>Tuyệt đối không chuyển tiền nếu thông tin ngân hàng trên ứng dụng khác với hóa đơn ShopKey.</p></div></aside>
    </div></section>

    @if($deposits->isNotEmpty())
        <section class="section bank-history-section"><div class="container customer-panel"><div class="customer-panel__head"><div><h2>Hóa đơn ngân hàng gần đây</h2><p>Trạng thái được cập nhật từ SePay.</p></div></div><div class="customer-table-wrap"><table class="customer-table"><thead><tr><th>Mã hóa đơn</th><th>Ngân hàng</th><th>Số tiền</th><th>Trạng thái</th><th>Thời gian</th><th></th></tr></thead><tbody>@foreach($deposits as $deposit)<tr><td><code>{{ $deposit->invoice_code }}</code></td><td>{{ $deposit->bankAccount->bank_name }}</td><td><strong>{{ number_format($deposit->amount, 0, ',', '.') }}₫</strong></td><td><span class="customer-status customer-status--{{ $deposit->status->value }}">{{ $deposit->status->label() }}</span></td><td>{{ $deposit->created_at->format('d/m/Y H:i') }}</td><td><a href="{{ route('wallet.deposit.bank.show', $deposit) }}">Xem QR</a></td></tr>@endforeach</tbody></table></div><nav>{{ $deposits->links() }}</nav></div></section>
    @endif
@endsection
