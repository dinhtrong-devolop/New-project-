<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Account\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        return $this->success((new UserResource($request->user()->load('wallet')))->resolve(), 'Profile retrieved successfully');
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $emailChanged = $user->email !== $request->string('email')->value();
        $user->fill($request->validated());

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $this->success((new UserResource($user->load('wallet')))->resolve(), 'Profile updated successfully');
    }
}
