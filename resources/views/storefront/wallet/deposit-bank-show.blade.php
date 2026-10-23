@extends('layouts.storefront')

@section('title', 'Thanh toán '.$deposit->invoice_code)

@section('content')
    <div class="page-hero page-hero--small"><div class="container"><nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('wallet.deposit.bank') }}">Nạp qua ngân hàng</a><span>/</span><strong>{{ $deposit->invoice_code }}</strong></nav><h1>Quét QR để nạp tiền</h1><p>Hóa đơn tự động đối soát qua SePay.</p></div></div>

    <section class="section"><div class="container bank-invoice" data-bank-deposit-status-url="{{ route('wallet.deposit.bank.status', $deposit) }}" data-bank-deposit-current-status="{{ $deposit->status->value }}">
        <div class="bank-qr-card">
            <div class="bank-invoice-status bank-invoice-status--{{ $deposit->status->value }}" data-bank-status><span><x-icon name="{{ $deposit->status === App\Enums\BankDepositStatus::Paid ? 'check' : 'clock' }}" :size="19" /></span><div><strong data-bank-status-label>{{ match($deposit->status) { App\Enums\BankDepositStatus::Paid => 'Đã cộng tiền vào ví', App\Enums\BankDepositStatus::Review => 'Giao dịch đang được kiểm tra', App\Enums\BankDepositStatus::Expired => 'Hóa đơn đã hết hạn', App\Enums\BankDepositStatus::Cancelled => 'Hóa đơn đã hủy', default => 'Đang chờ chuyển khoản' } }}</strong><small data-bank-status-note>{{ $deposit->status === App\Enums\BankDepositStatus::Paid ? 'Bạn có thể sử dụng số dư ngay.' : 'Trang tự cập nhật khi SePay xác nhận giao dịch.' }}</small></div></div>
            <div class="bank-qr-image"><img src="{{ $qrUrl }}" alt="Mã QR chuyển khoản {{ $deposit->invoice_code }}" width="420" height="420"></div>
            <p class="bank-qr-powered">QR được tạo qua VietQR · Đối soát bởi SePay</p>
        </div>

        <div class="bank-transfer-card">
            <span class="admin-eyebrow">THÔNG TIN CHUYỂN KHOẢN</span><h2>{{ $deposit->bankAccount->bank_name }}</h2><p>Kiểm tra chính xác tất cả thông tin trước khi xác nhận trong ứng dụng ngân hàng.</p>
            <div class="bank-transfer-details">
                <div><span>Số tài khoản</span><strong>{{ $deposit->bankAccount->account_number }}</strong><button type="button" data-copy-value="{{ $deposit->bankAccount->account_number }}">Sao chép</button></div>
                <div><span>Chủ tài khoản</span><strong>{{ $deposit->bankAccount->account_holder }}</strong></div>
                <div><span>Số tiền</span><strong class="bank-transfer-amount">{{ number_format($deposit->amount, 0, ',', '.') }}₫</strong><button type="button" data-copy-value="{{ $deposit->amount }}">Sao chép</button></div>
                <div class="bank-transfer-content"><span>Nội dung chuyển khoản</span><strong>{{ $transferDescription }}</strong><button type="button" data-copy-value="{{ $transferDescription }}">Sao chép</button></div>
            </div>
            <div class="bank-invoice-meta"><span>Mã hóa đơn <b>{{ $deposit->invoice_code }}</b></span><span>Hết hạn <b>{{ $deposit->expires_at?->format('H:i d/m/Y') }}</b></span></div>
            <div class="deposit-warning"><x-icon name="shield" :size="20" /><p>Không thay đổi số tiền và nội dung. Sai thông tin sẽ chuyển sang kiểm tra thủ công thay vì tự động cộng ví.</p></div>
            <div class="bank-invoice-actions"><a href="{{ route('wallet.index') }}" class="button button--secondary">Về ví ShopKey</a><a href="{{ route('wallet.deposit.bank') }}" class="button button--primary">Tạo hóa đơn khác</a></div>
        </div>
    </div></section>
@endsection
