<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, CartService $carts): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create([
                'name' => $request->string('name')->value(),
                'email' => $request->string('email')->value(),
                'phone' => $request->input('phone'),
                'password' => $request->string('password')->value(),
                'role' => UserRole::Customer,
            ]);

            $user->wallet()->create(['balance' => 0, 'currency' => 'VND']);
            $user->cart()->create();

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        $carts->resolve($request);

        return redirect()->route('account.index')->with('status', 'Tài khoản ShopKey đã được tạo thành công.');
    }
}
