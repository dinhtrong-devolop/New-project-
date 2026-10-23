<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AccountPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()
            ->load([
                'wallet.transactions' => fn ($query) => $query->latest()->limit(6),
                'orders' => fn ($query) => $query->withCount('items')->latest()->limit(6),
                'cardDeposits' => fn ($query) => $query->latest()->limit(6),
            ])
            ->loadCount(['orders', 'cardDeposits']);

        return view('storefront.account', compact('user'));
    }
}
