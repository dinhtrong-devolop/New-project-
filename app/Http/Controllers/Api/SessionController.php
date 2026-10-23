<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $currentId = $request->user()->currentAccessToken()?->getKey();
        $sessions = $request->user()->tokens()->latest()->get()->map(fn ($token): array => [
            'id' => $token->id,
            'name' => $token->name,
            'current' => (int) $token->id === (int) $currentId,
            'last_used_at' => $token->last_used_at?->toISOString(),
            'expires_at' => $token->expires_at?->toISOString(),
            'created_at' => $token->created_at?->toISOString(),
        ])->all();

        return $this->success($sessions, 'Sessions retrieved successfully');
    }

    public function destroy(Request $request, int $token): JsonResponse
    {
        $session = $request->user()->tokens()->whereKey($token)->firstOrFail();
        $session->delete();

        return $this->success(null, 'Session revoked successfully');
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->success(null, 'All sessions revoked successfully');
    }
}
