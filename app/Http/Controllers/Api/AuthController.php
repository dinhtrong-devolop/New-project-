<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function register(RegisterRequest $request): JsonResponse
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
        $token = $user->createToken($request->string('device_name')->value() ?: 'mobile')->plainTextToken;

        return $this->success([
            'user' => (new UserResource($user->load('wallet')))->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Account created successfully', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->value())->first();

        if (! $user || ! Hash::check($request->string('password')->value(), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không chính xác.']);
        }

        if ($user->locked_at) {
            throw ValidationException::withMessages(['email' => 'Tài khoản đang bị khóa.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken($request->string('device_name')->value())->plainTextToken;

        return $this->success([
            'user' => (new UserResource($user->load('wallet')))->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logout successful');
    }
}
