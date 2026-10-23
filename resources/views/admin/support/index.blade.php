@extends('layouts.admin')

@section('title', 'Hỗ trợ khách hàng')
@section('page_title', 'Hộp thư hỗ trợ')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">CUSTOMER SUPPORT</span><h1>Hỗ trợ khách hàng</h1><p>Nhận và trả lời tin nhắn riêng tư của thành viên đã đăng nhập.</p></div></div>

    <div class="admin-support-stats"><span><b>{{ $stats['open'] }}</b> đang mở</span><span><b>{{ $stats['unread'] }}</b> chưa đọc</span><span><b>{{ $stats['closed'] }}</b> đã đóng</span></div>

    <div class="admin-support-layout">
        <aside class="admin-panel admin-support-inbox">
            <form method="GET" class="admin-support-filter"><div class="admin-search"><x-icon name="search" :size="16" /><input name="search" value="{{ request('search') }}" placeholder="Tên hoặc email"></div><select name="status"><option value="">Tất cả</option><option value="open" @selected(request('status') === 'open')>Đang mở</option><option value="closed" @selected(request('status') === 'closed')>Đã đóng</option></select><button class="admin-button">Lọc</button></form>
            <div class="admin-support-list">
                @forelse($conversations as $conversation)
                    <a href="{{ route('admin.support.index', array_filter(['conversation' => $conversation->id, 'status' => request('status'), 'search' => request('search')])) }}" @class(['is-active' => $selected?->id === $conversation->id])>
                        <span>{{ str($conversation->user->name)->substr(0, 2)->upper() }}</span><div><strong>{{ $conversation->user->name }}</strong><small>{{ $conversation->user->email }}</small><em>{{ $conversation->last_message_at?->diffForHumans() ?: 'Chưa có tin' }}</em></div>@if($conversation->unread_count > 0)<b>{{ min($conversation->unread_count, 99) }}</b>@endif
                    </a>
                @empty
                    <div class="admin-empty">Chưa có cuộc trò chuyện hỗ trợ.</div>
                @endforelse
            </div>
            <div class="admin-pagination">{{ $conversations->links() }}</div>
        </aside>

        <section class="admin-panel admin-support-thread">
            @if($selected)
                <header><div class="admin-user-cell"><span>{{ str($selected->user->name)->substr(0, 2)->upper() }}</span><div><strong>{{ $selected->user->name }}</strong><small>{{ $selected->user->email }} · #{{ $selected->id }}</small></div></div><div><span class="admin-badge admin-badge--{{ $selected->status->value }}">{{ $selected->status === App\Enums\SupportConversationStatus::Open ? 'Đang mở' : 'Đã đóng' }}</span><form action="{{ route('admin.support.status', $selected) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $selected->status === App\Enums\SupportConversationStatus::Open ? 'closed' : 'open' }}"><button class="admin-button">{{ $selected->status === App\Enums\SupportConversationStatus::Open ? 'Đóng chat' : 'Mở lại' }}</button></form></div></header>
                <div class="admin-support-messages" data-admin-support-messages data-messages-url="{{ route('admin.support.messages', $selected) }}">
                    @forelse($selected->messages as $message)
                        <article @class(['is-mine' => $message->sender_id === auth()->id(), 'is-admin' => $message->sender->role === App\Enums\UserRole::Admin])><div><strong>{{ $message->sender->name }}</strong><p>{!! nl2br(e($message->message)) !!}</p><small>{{ $message->created_at->format('H:i d/m/Y') }}</small></div></article>
                    @empty<div class="admin-empty">Hội thoại chưa có tin nhắn.</div>@endforelse
                </div>
                <form action="{{ route('admin.support.reply', $selected) }}" method="POST" class="admin-support-reply" data-admin-support-reply @if($selected->status === App\Enums\SupportConversationStatus::Closed) hidden @endif>@csrf<textarea name="message" rows="2" maxlength="2000" required placeholder="Nhập phản hồi cho khách hàng..."></textarea><button class="admin-button admin-button--primary"><x-icon name="arrow-right" :size="17" /> Gửi phản hồi</button></form>
                @if($selected->status === App\Enums\SupportConversationStatus::Closed)<div class="admin-support-closed"><x-icon name="lock" :size="17" /> Cuộc trò chuyện đã đóng. Mở lại để tiếp tục trả lời.</div>@endif
            @else
                <div class="admin-support-placeholder"><x-icon name="support" :size="38" /><h2>Chưa chọn hội thoại</h2><p>Chọn một khách hàng bên trái để xem và trả lời tin nhắn.</p></div>
            @endif
        </section>
    </div>
@endsection
