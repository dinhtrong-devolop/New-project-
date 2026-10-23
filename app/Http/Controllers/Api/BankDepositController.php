<?php

namespace App\Http\Controllers\Api;

use App\Enums\BankDepositStatus;
use App\Http\Requests\BankDepositRequest;
use App\Http\Resources\BankAccountResource;
use App\Http\Resources\BankDepositResource;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Services\BankDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BankDepositController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $deposits = $request->user()->bankDeposits()->with('bankAccount')->latest()
            ->paginate(min(max($request->integer('per_page', 15), 1), 50));

        return $this->success(
            BankDepositResource::collection($deposits->getCollection())->resolve(),
            'Bank deposits retrieved successfully',
            200,
            ['current_page' => $deposits->currentPage(), 'last_page' => $deposits->lastPage(), 'total' => $deposits->total()],
        );
    }

    public function accounts(): JsonResponse
    {
        $accounts = BankAccount::query()->where('is_visible', true)->orderBy('bank_name')->get();

        return $this->success(BankAccountResource::collection($accounts)->resolve(), 'Bank accounts retrieved successfully');
    }

    public function store(BankDepositRequest $request, BankDepositService $service): JsonResponse
    {
        $account = BankAccount::query()->whereKey($request->integer('bank_account_id'))->where('is_visible', true)->firstOrFail();
        $deposit = $service->createInvoice($request->user(), $account, $request->integer('amount'))->load('bankAccount');

        return $this->success((new BankDepositResource($deposit))->resolve(), 'Bank deposit created successfully', 201);
    }

    public function show(Request $request, BankDeposit $bankDeposit): JsonResponse
    {
        $this->authorizeOwner($request, $bankDeposit);
        $this->expire($bankDeposit);

        return $this->success((new BankDepositResource($bankDeposit->fresh()->load('bankAccount')))->resolve(), 'Bank deposit retrieved successfully');
    }

    public function status(Request $request, BankDeposit $bankDeposit): JsonResponse
    {
        return $this->show($request, $bankDeposit);
    }

    public function cancel(Request $request, BankDeposit $bankDeposit): JsonResponse
    {
        $this->authorizeOwner($request, $bankDeposit);
        $this->expire($bankDeposit);
        $bankDeposit->refresh();

        if ($bankDeposit->status !== BankDepositStatus::Pending) {
            throw ValidationException::withMessages(['deposit' => 'Chỉ có thể hủy hóa đơn đang chờ chuyển khoản.']);
        }

        $bankDeposit->update(['status' => BankDepositStatus::Cancelled]);

        return $this->success((new BankDepositResource($bankDeposit->fresh()->load('bankAccount')))->resolve(), 'Bank deposit cancelled successfully');
    }

    private function authorizeOwner(Request $request, BankDeposit $deposit): void
    {
        abort_unless((int) $deposit->user_id === (int) $request->user()->id, 404);
    }

    private function expire(BankDeposit $deposit): void
    {
        if ($deposit->status === BankDepositStatus::Pending && $deposit->expires_at?->isPast()) {
            $deposit->update(['status' => BankDepositStatus::Expired]);
        }
    }
}
