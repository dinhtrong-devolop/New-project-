<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CardDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NaptudongCallbackController extends Controller
{
    public function __invoke(Request $request, CardDepositService $deposits): JsonResponse
    {
        $payload = $request->validate([
            'request_id' => ['required', 'string', 'max:40'],
            'status' => ['required', 'integer'],
            'message' => ['nullable', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'code' => ['required', 'string', 'max:32'],
            'serial' => ['required', 'string', 'max:32'],
            'telco' => ['nullable', 'string', 'max:30'],
            'trans_id' => ['nullable'],
            'callback_sign' => ['required', 'string', 'size:32'],
        ]);

        try {
            $deposit = $deposits->processCallback($payload);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Callback processed successfully',
            'data' => ['request_id' => $deposit->request_id, 'status' => $deposit->status->value],
        ]);
    }
}
