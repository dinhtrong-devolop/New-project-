<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BankDepositStatus;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Services\ActivityLogger;
use App\Services\BankDepositService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankDepositController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(BankDepositStatus::class)],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = BankDeposit::query()->with(['user', 'bankAccount', 'transaction'])->latest();

        $query->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status));
        $query->when($filters['bank_account_id'] ?? null, fn ($builder, $id) => $builder->where('bank_account_id', $id));
        $query->when($filters['search'] ?? null, function ($builder, $search): void {
            $builder->where(function ($inner) use ($search): void {
                $inner->where('invoice_code', 'like', "%{$search}%")
                    ->orWhere('reference_code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$search}%"));
            });
        });

        $stats = [
            'total' => BankDeposit::query()->count(),
            'pending' => BankDeposit::query()->whereIn('status', [BankDepositStatus::Pending, BankDepositStatus::Review])->count(),
            'paid' => BankDeposit::query()->where('status', BankDepositStatus::Paid)->count(),
            'paid_amount' => (int) BankDeposit::query()->where('status', BankDepositStatus::Paid)->sum('received_amount'),
        ];

        return view('admin.bank-deposits.index', [
            'deposits' => $query->paginate(20)->withQueryString(),
            'accounts' => BankAccount::query()->orderBy('bank_name')->get(),
            'stats' => $stats,
        ]);
    }

    public function approve(
        Request $request,
        BankDeposit $bankDeposit,
        BankDepositService $service,
        ActivityLogger $logger,
    ): RedirectResponse {
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:1000']]);
        $approved = $service->approve($bankDeposit, $request->user(), $data['note']);
        $logger->log($request, 'admin.bank_deposit.approved', $approved, [
            'invoice_code' => $approved->invoice_code,
            'received_amount' => $approved->received_amount,
        ]);

        return back()->with('status', 'Đã duyệt và cộng '.number_format($approved->received_amount, 0, ',', '.').'₫ vào ví khách hàng.');
    }
}
