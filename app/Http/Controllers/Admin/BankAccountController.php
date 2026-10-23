<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BankAccountRequest;
use App\Models\BankAccount;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BankAccountController extends Controller
{
    public function index(): View
    {
        $accounts = BankAccount::query()
            ->withCount('deposits')
            ->withSum(['deposits as paid_total' => fn ($query) => $query->where('status', 'paid')], 'received_amount')
            ->latest()
            ->paginate(20);

        return view('admin.bank-accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('admin.bank-accounts.form', [
            'account' => new BankAccount([
                'provider' => 'sepay',
                'deposit_prefix' => 'SKN',
                'minimum_deposit' => 10000,
                'is_visible' => true,
                'auto_confirm' => true,
                'webhook_auth' => 'hmac',
            ]),
            'banks' => config('vietnam_banks', []),
            'callbackUrl' => null,
        ]);
    }

    public function store(BankAccountRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->attributes($request);

        if ($data['auto_confirm'] && blank($data['webhook_secret'] ?? null)) {
            throw ValidationException::withMessages(['webhook_secret' => 'Cần nhập khóa xác thực để bật tự động cộng tiền.']);
        }

        $account = DB::transaction(fn () => BankAccount::query()->create($data + [
            'webhook_key' => (string) Str::ulid(),
            'configured_by' => $request->user()->id,
        ]));

        $logger->log($request, 'admin.bank_account.created', $account, $this->logMetadata($account));

        return redirect()->route('admin.bank-accounts.edit', $account)
            ->with('status', 'Đã thêm tài khoản ngân hàng. Hãy sao chép webhook URL sang SePay.');
    }

    public function edit(BankAccount $bankAccount): View
    {
        return view('admin.bank-accounts.form', [
            'account' => $bankAccount,
            'banks' => config('vietnam_banks', []),
            'callbackUrl' => route('payment.sepay.webhook', $bankAccount->webhook_key),
        ]);
    }

    public function update(BankAccountRequest $request, BankAccount $bankAccount, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->attributes($request);

        if (blank($data['api_token'] ?? null)) {
            unset($data['api_token']);
        }

        if (blank($data['webhook_secret'] ?? null)) {
            unset($data['webhook_secret']);
        }

        if ($data['auto_confirm'] && blank($data['webhook_secret'] ?? $bankAccount->webhook_secret)) {
            throw ValidationException::withMessages(['webhook_secret' => 'Cần có khóa xác thực để bật tự động cộng tiền.']);
        }

        $bankAccount->update($data + ['configured_by' => $request->user()->id]);
        $logger->log($request, 'admin.bank_account.updated', $bankAccount, $this->logMetadata($bankAccount));

        return back()->with('status', 'Đã lưu cấu hình tài khoản ngân hàng.');
    }

    public function toggle(Request $request, BankAccount $bankAccount, ActivityLogger $logger): RedirectResponse
    {
        $bankAccount->update([
            'is_visible' => ! $bankAccount->is_visible,
            'configured_by' => $request->user()->id,
        ]);
        $logger->log($request, 'admin.bank_account.visibility_toggled', $bankAccount, $this->logMetadata($bankAccount));

        return back()->with('status', $bankAccount->is_visible ? 'Đã hiển thị tài khoản nhận tiền.' : 'Đã ẩn tài khoản nhận tiền.');
    }

    /** @return array<string, mixed> */
    private function attributes(BankAccountRequest $request): array
    {
        $data = $request->validated();
        $data['bank_name'] = config('vietnam_banks.'.$data['bank_code'], $data['bank_code']);

        return $data;
    }

    /** @return array<string, mixed> */
    private function logMetadata(BankAccount $account): array
    {
        return [
            'provider' => $account->provider,
            'bank_code' => $account->bank_code,
            'account_number_last4' => substr($account->account_number, -4),
            'is_visible' => $account->is_visible,
            'auto_confirm' => $account->auto_confirm,
        ];
    }
}
