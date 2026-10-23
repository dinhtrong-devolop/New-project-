<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VoucherRequest;
use App\Models\Voucher;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(): View
    {
        return view('admin.vouchers.index', [
            'vouchers' => Voucher::query()->latest()->paginate(20),
        ]);
    }

    public function store(VoucherRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $voucher = Voucher::query()->create($request->validated());
        $logger->log($request, 'admin.voucher.created', $voucher, ['code' => $voucher->code]);

        return back()->with('status', 'Đã tạo voucher.');
    }

    public function toggle(Request $request, Voucher $voucher, ActivityLogger $logger): RedirectResponse
    {
        $voucher->update(['is_active' => ! $voucher->is_active]);
        $logger->log($request, 'admin.voucher.toggled', $voucher, ['is_active' => $voucher->is_active]);

        return back()->with('status', 'Đã cập nhật trạng thái voucher.');
    }
}
