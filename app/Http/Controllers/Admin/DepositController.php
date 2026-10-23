<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardDeposit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function index(Request $request): View
    {
        $deposits = CardDeposit::query()
            ->with('user')
            ->when($request->string('status')->value(), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->string('search')->trim()->value(), fn (Builder $query, string $search) => $query
                ->where(fn (Builder $inner) => $inner->where('request_id', 'like', "%{$search}%")->orWhere('provider_transaction_id', 'like', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.deposits.index', compact('deposits'));
    }
}
