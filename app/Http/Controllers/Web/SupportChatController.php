<?php

namespace App\Http\Controllers\Web;

use App\Enums\SupportConversationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conversation = SupportConversation::query()
            ->where('user_id', $request->user()->id)
            ->with(['messages' => fn ($query) => $query->with('sender:id,name,role')->orderByDesc('id')->limit(100)])
            ->first();

        return response()->json([
            'conversation' => $conversation ? [
                'id' => $conversation->id,
                'status' => $conversation->status->value,
            ] : null,
            'messages' => $conversation
                ? $conversation->messages->reverse()->values()->map(fn (SupportMessage $message): array => $this->messagePayload($message, $request->user()->id))->all()
                : [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $message = DB::transaction(function () use ($request, $data): SupportMessage {
            $conversation = SupportConversation::query()->firstOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'subject' => 'Hỗ trợ tài khoản',
                    'status' => SupportConversationStatus::Open,
                ],
            );
            $conversation = SupportConversation::query()->whereKey($conversation->id)->lockForUpdate()->firstOrFail();

            if ($conversation->status === SupportConversationStatus::Closed) {
                throw ValidationException::withMessages(['message' => 'Cuộc trò chuyện đã đóng. Vui lòng chờ quản trị viên mở lại.']);
            }

            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'message' => trim($data['message']),
            ]);
            $conversation->update(['last_message_at' => now()]);

            return $message->load('sender:id,name,role');
        });

        return response()->json(['message' => $this->messagePayload($message, $request->user()->id)], 201);
    }

    public function read(Request $request): JsonResponse
    {
        $conversationId = SupportConversation::query()->where('user_id', $request->user()->id)->value('id');

        if ($conversationId) {
            SupportMessage::query()
                ->where('support_conversation_id', $conversationId)
                ->where('sender_id', '!=', $request->user()->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    /** @return array<string, mixed> */
    private function messagePayload(SupportMessage $message, int $viewerId): array
    {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'mine' => (int) $message->sender_id === $viewerId,
            'sender_name' => $message->sender->name,
            'is_admin' => $message->sender->role === UserRole::Admin,
            'created_at' => $message->created_at->format('H:i d/m/Y'),
        ];
    }
}
