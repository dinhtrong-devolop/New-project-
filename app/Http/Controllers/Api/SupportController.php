<?php

namespace App\Http\Controllers\Api;

use App\Enums\SupportConversationStatus;
use App\Enums\UserRole;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $conversation = SupportConversation::query()->where('user_id', $request->user()->id)
            ->with(['messages' => fn ($query) => $query->with('sender:id,name,role')->latest('id')->limit(100)])
            ->first();

        return $this->success([
            'conversation' => $conversation ? ['id' => $conversation->id, 'status' => $conversation->status->value, 'subject' => $conversation->subject] : null,
            'messages' => $conversation ? $conversation->messages->reverse()->values()->map(fn (SupportMessage $message) => $this->payload($message, $request->user()->id))->all() : [],
        ], 'Support messages retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);
        $message = DB::transaction(function () use ($request, $data): SupportMessage {
            $conversation = SupportConversation::query()->firstOrCreate(
                ['user_id' => $request->user()->id],
                ['subject' => 'Hỗ trợ tài khoản', 'status' => SupportConversationStatus::Open],
            );
            $conversation = SupportConversation::query()->whereKey($conversation->id)->lockForUpdate()->firstOrFail();

            if ($conversation->status === SupportConversationStatus::Closed) {
                throw ValidationException::withMessages(['message' => 'Cuộc trò chuyện đã đóng. Vui lòng chờ quản trị viên mở lại.']);
            }

            $message = $conversation->messages()->create(['sender_id' => $request->user()->id, 'message' => trim($data['message'])]);
            $conversation->update(['last_message_at' => now()]);

            return $message->load('sender:id,name,role');
        });

        return $this->success($this->payload($message, $request->user()->id), 'Support message sent successfully', 201);
    }

    public function read(Request $request): JsonResponse
    {
        $conversationId = SupportConversation::query()->where('user_id', $request->user()->id)->value('id');
        if ($conversationId) {
            SupportMessage::query()->where('support_conversation_id', $conversationId)->where('sender_id', '!=', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        }

        return $this->success(null, 'Support messages marked as read');
    }

    private function payload(SupportMessage $message, int $viewerId): array
    {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'mine' => (int) $message->sender_id === $viewerId,
            'sender_name' => $message->sender->name,
            'is_admin' => $message->sender->role === UserRole::Admin,
            'read_at' => $message->read_at?->toISOString(),
            'created_at' => $message->created_at?->toISOString(),
        ];
    }
}
