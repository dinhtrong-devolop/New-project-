<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CardDepositRequest;
use App\Services\CardDepositService;
use App\Services\NaptudongService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $wallet = $request->user()->wallet()->firstOrCreate([], ['balance' => 0, 'currency' => 'VND']);
        $transactions = $wallet->transactions()->latest()->paginate(15);

        return view('storefront.wallet.index', compact('wallet', 'transactions'));
    }

    public function deposits(Request $request): View
    {
        $deposits = $request->user()->cardDeposits()->latest()->paginate(15);

        return view('storefront.wallet.deposits', compact('deposits'));
    }

    public function createDeposit(NaptudongService $provider): View
    {
        return view('storefront.wallet.deposit-card', [
            'providers' => config('services.naptudong.providers', []),
            'denominations' => config('services.naptudong.denominations', []),
            'configured' => $provider->configured(),
            'discountPercent' => $provider->discountPercent(),
        ]);
    }

    public function storeDeposit(CardDepositRequest $request, CardDepositService $deposits): RedirectResponse
    {
        $deposit = $deposits->submit(
            $request->user(),
            $request->string('provider')->value(),
            $request->integer('amount'),
            $request->string('serial')->value(),
            $request->string('code')->value(),
        );

        return redirect()->route('wallet.deposits')
            ->with('status', 'Đã gửi thẻ với mã yêu cầu '.$deposit->request_id.'. Vui lòng chờ đối tác xử lý.');
    }
}
