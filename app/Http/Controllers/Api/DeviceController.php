<?php

namespace App\Http\Controllers\Api;

use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::in(['android', 'ios', 'web'])],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $device = DeviceToken::query()->updateOrCreate(
            ['token' => $data['token']],
            ['user_id' => $request->user()->id, 'platform' => $data['platform'], 'device_name' => $data['device_name'] ?? null, 'last_used_at' => now()],
        );

        return $this->success($this->payload($device), 'Device registered successfully', $device->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, DeviceToken $device): JsonResponse
    {
        abort_unless((int) $device->user_id === (int) $request->user()->id, 404);
        $device->delete();

        return $this->success(null, 'Device removed successfully');
    }

    private function payload(DeviceToken $device): array
    {
        return [
            'id' => $device->id,
            'platform' => $device->platform,
            'device_name' => $device->device_name,
            'last_used_at' => $device->last_used_at?->toISOString(),
        ];
    }
}
