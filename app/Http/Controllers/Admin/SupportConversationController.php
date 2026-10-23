<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportConversationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupportConversationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(SupportConversationStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'conversation' => ['nullable', 'integer', 'exists:support_conversations,id'],
        ]);

        $query = SupportConversation::query()
            ->with(['user:id,name,email', 'assignedTo:id,name'])
            ->withCount(['messages as unread_count' => fn ($builder) => $builder
                ->whereNull('read_at')
                ->whereColumn('support_messages.sender_id', 'support_conversations.user_id')])
            ->when($filters['status'] ?? null, fn ($builder, $status) => $builder->where('status', $status))
            ->when($filters['search'] ?? null, fn ($builder, $search) => $builder->whereHas('user', fn ($user) => $user
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderByDesc('last_message_at');

        $conversations = $query->paginate(30)->withQueryString();
        $selectedId = (int) ($filters['conversation'] ?? $conversations->first()?->id);
        $selected = $selectedId ? SupportConversation::query()->with('user:id,name,email')->find($selectedId) : null;

        if ($selected) {
            $this->markCustomerMessagesRead($selected);
            $selected->load(['messages' => fn ($builder) => $builder->with('sender:id,name,role')->orderByDesc('id')->limit(150)]);
            $selected->setRelation('messages', $selected->messages->reverse()->values());
        }

        return view('admin.support.index', [
            'conversations' => $conversations,
            'selected' => $selected,
            'stats' => [
                'open' => SupportConversation::query()->where('status', SupportConversationStatus::Open)->count(),
                'closed' => SupportConversation::query()->where('status', SupportConversationStatus::Closed)->count(),
                'unread' => $this->adminUnreadCount(),
            ],
        ]);
    }

    public function messages(Request $request, SupportConversation $supportConversation): JsonResponse
    {
        $this->markCustomerMessagesRead($supportConversation);
        $messages = $supportConversation->messages()->with('sender:id,name,role')->orderByDesc('id')->limit(150)->get()->reverse()->values();

        return response()->json([
            'status' => $supportConversation->fresh()->status->value,
            'messages' => $messages->map(fn (SupportMessage $message): array => $this->messagePayload($message, $request->user()->id))->all(),
        ]);
    }

    public function reply(Request $request, SupportConversation $supportConversation): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $message = DB::transaction(function () use ($request, $supportConversation, $data): SupportMessage {
            $conversation = SupportConversation::query()->whereKey($supportConversation->id)->lockForUpdate()->firstOrFail();

            if ($conversation->status === SupportConversationStatus::Closed) {
                throw ValidationException::withMessages(['message' => 'Hãy mở lại cuộc trò chuyện trước khi trả lời.']);
            }

            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'message' => trim($data['message']),
            ]);
            $conversation->update([
                'assigned_to' => $conversation->assigned_to ?: $request->user()->id,
                'last_message_at' => now(),
            ]);

            return $message->load('sender:id,name,role');
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => $this->messagePayload($message, $request->user()->id)], 201);
        }

        return back()->with('status', 'Đã gửi phản hồi cho khách hàng.');
    }

    public function updateStatus(
        Request $request,
        SupportConversation $supportConversation,
        ActivityLogger $logger,
    ): RedirectResponse {
        $data = $request->validate(['status' => ['required', Rule::enum(SupportConversationStatus::class)]]);
        $supportConversation->update([
            'status' => $data['status'],
            'assigned_to' => $supportConversation->assigned_to ?: $request->user()->id,
        ]);
        $logger->log($request, 'admin.support.status_updated', $supportConversation, ['status' => $data['status']]);

        return back()->with('status', $data['status'] === SupportConversationStatus::Open->value ? 'Đã mở lại cuộc trò chuyện.' : 'Đã đóng cuộc trò chuyện.');
    }

    private function markCustomerMessagesRead(SupportConversation $conversation): void
    {
        $conversation->messages()->where('sender_id', $conversation->user_id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    private function adminUnreadCount(): int
    {
        return SupportMessage::query()
            ->whereNull('read_at')
            ->whereHas('conversation', fn ($query) => $query->whereColumn('support_messages.sender_id', 'support_conversations.user_id'))
            ->count();
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
