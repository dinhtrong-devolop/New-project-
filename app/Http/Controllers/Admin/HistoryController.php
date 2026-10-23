<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardDeposit;
use App\Models\Order;
use App\Models\VoucherUsage;
use App\Models\WalletTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function transactions(Request $request): View
    {
        $transactions = WalletTransaction::query()
            ->with('user')
            ->when($request->string('search')->trim()->value(), function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhere('idempotency_key', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $user) => $user
                            ->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"));

                    if (ctype_digit($search)) {
                        $inner->orWhereKey((int) $search);
                    }
                });
            })
            ->when($request->string('type')->value(), fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($request->string('status')->value(), fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.history.transactions', compact('transactions'));
    }

    public function orders(Request $request): View
    {
        $orders = Order::query()
            ->with('user')
            ->withCount('items')
            ->when($request->string('search')->trim()->value(), fn (Builder $query, string $search) => $query
                ->where(fn (Builder $inner) => $inner
                    ->where('order_code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"))))
            ->when($request->string('status')->value(), fn (Builder $query, string $status) => $query->where('order_status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.history.orders', compact('orders'));
    }

    public function cardDeposits(Request $request): View
    {
        $deposits = CardDeposit::query()
            ->with('user')
            ->when($request->string('search')->trim()->value(), fn (Builder $query, string $search) => $query
                ->where(fn (Builder $inner) => $inner
                    ->where('request_id', 'like', "%{$search}%")
                    ->orWhere('provider_transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"))))
            ->when($request->string('status')->value(), fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.history.card-deposits', compact('deposits'));
    }

    public function voucherUsages(Request $request): View
    {
        $usages = VoucherUsage::query()
            ->with(['voucher', 'user', 'order'])
            ->when($request->string('search')->trim()->value(), fn (Builder $query, string $search) => $query
                ->where(fn (Builder $inner) => $inner
                    ->whereHas('voucher', fn (Builder $voucher) => $voucher->where('code', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn (Builder $order) => $order->where('order_code', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"))))
            ->latest('used_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.history.voucher-usages', compact('usages'));
    }
}
