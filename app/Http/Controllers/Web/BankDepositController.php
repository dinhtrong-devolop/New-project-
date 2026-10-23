<?php

namespace App\Http\Controllers\Web;

use App\Enums\BankDepositStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BankDepositRequest;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Services\BankDepositService;
use App\Services\SepayService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BankDepositController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = BankAccount::query()->where('is_visible', true)->orderBy('bank_name')->get();
        $deposits = $request->user()->bankDeposits()->with('bankAccount')->latest()->paginate(10);

        return view('storefront.wallet.deposit-bank', compact('accounts', 'deposits'));
    }

    public function store(BankDepositRequest $request, BankDepositService $service): RedirectResponse
    {
        $account = BankAccount::query()->whereKey($request->integer('bank_account_id'))->firstOrFail();
        $deposit = $service->createInvoice($request->user(), $account, $request->integer('amount'));

        return redirect()->route('wallet.deposit.bank.show', $deposit);
    }

    public function show(Request $request, BankDeposit $bankDeposit, SepayService $sepay): View
    {
        $this->authorizeOwner($request, $bankDeposit);
        $this->expireIfNecessary($bankDeposit);
        $bankDeposit->load('bankAccount');

        return view('storefront.wallet.deposit-bank-show', [
            'deposit' => $bankDeposit,
            'qrUrl' => $sepay->qrUrl($bankDeposit),
            'transferDescription' => $sepay->transferDescription($bankDeposit),
        ]);
    }

    public function status(Request $request, BankDeposit $bankDeposit): JsonResponse
    {
        $this->authorizeOwner($request, $bankDeposit);
        $this->expireIfNecessary($bankDeposit);
        $bankDeposit->refresh();

        return response()->json([
            'status' => $bankDeposit->status->value,
            'status_label' => match ($bankDeposit->status) {
                BankDepositStatus::Pending => 'Đang chờ chuyển khoản',
                BankDepositStatus::Paid => 'Đã cộng tiền',
                BankDepositStatus::Review => 'Đang chờ kiểm tra',
                BankDepositStatus::Expired => 'Đã hết hạn',
                BankDepositStatus::Cancelled => 'Đã hủy',
            },
            'paid_at' => $bankDeposit->paid_at?->toIso8601String(),
        ]);
    }

    private function authorizeOwner(Request $request, BankDeposit $deposit): void
    {
        abort_unless((int) $deposit->user_id === (int) $request->user()->id, 404);
    }

    private function expireIfNecessary(BankDeposit $deposit): void
    {
        if ($deposit->status === BankDepositStatus::Pending && $deposit->expires_at?->isPast()) {
            $deposit->update(['status' => BankDepositStatus::Expired]);
        }
    }
}
