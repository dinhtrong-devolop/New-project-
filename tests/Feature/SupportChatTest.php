<?php

namespace Tests\Feature;

use App\Enums\SupportConversationStatus;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_asked_to_login_before_messaging_support(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Đăng nhập để nhắn tin');

        $this->post('/support/chat/messages', ['message' => 'Tôi cần hỗ trợ'])
            ->assertRedirect('/login');
    }

    public function test_authenticated_customer_can_start_private_conversation(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();

        $this->actingAs($customer)->get('/')
            ->assertOk()
            ->assertSee(route('support.chat.messages'))
            ->assertSee('Hội thoại riêng tư theo tài khoản đăng nhập');

        $this->actingAs($customer)->postJson('/support/chat/messages', [
            'message' => '<script>alert("xss")</script> Tôi cần kiểm tra đơn hàng.',
        ])->assertCreated()->assertJsonPath('message.mine', true);

        $conversation = SupportConversation::query()->firstOrFail();
        $this->assertSame($customer->id, $conversation->user_id);
        $this->assertSame(SupportConversationStatus::Open, $conversation->status);
        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $conversation->id,
            'sender_id' => $customer->id,
        ]);

        $this->actingAs($customer)->getJson('/support/chat/messages')
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.message', '<script>alert("xss")</script> Tôi cần kiểm tra đơn hàng.');

        $this->actingAs($otherCustomer)->getJson('/support/chat/messages')
            ->assertOk()
            ->assertJsonCount(0, 'messages');
    }

    public function test_admin_can_read_reply_and_close_customer_conversation(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($customer)->postJson('/support/chat/messages', [
            'message' => '<b>Đơn hàng chưa nhận được key</b>',
        ])->assertCreated();
        $conversation = SupportConversation::query()->firstOrFail();

        $this->actingAs($admin)->get('/admin/support?conversation='.$conversation->id)
            ->assertOk()
            ->assertSee('Đơn hàng chưa nhận được key')
            ->assertSee('&lt;b&gt;', false)
            ->assertDontSee('<b>Đơn hàng chưa nhận được key</b>', false);

        $this->assertNotNull(SupportMessage::query()->where('sender_id', $customer->id)->value('read_at'));

        $this->actingAs($admin)->postJson('/admin/support/'.$conversation->id.'/messages', [
            'message' => 'ShopKey đang kiểm tra và sẽ phản hồi ngay.',
        ])->assertCreated()->assertJsonPath('message.mine', true);

        $this->actingAs($customer)->getJson('/support/chat/messages')
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.1.is_admin', true);

        $this->actingAs($customer)->postJson('/support/chat/read')->assertOk();
        $this->assertNotNull(SupportMessage::query()->where('sender_id', $admin->id)->value('read_at'));

        $this->actingAs($admin)->patch('/admin/support/'.$conversation->id.'/status', [
            'status' => SupportConversationStatus::Closed->value,
        ])->assertSessionHasNoErrors();

        $this->actingAs($customer)->postJson('/support/chat/messages', [
            'message' => 'Tin nhắn sau khi đóng',
        ])->assertUnprocessable();

        $this->assertSame(SupportConversationStatus::Closed, $conversation->fresh()->status);
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $admin->id,
            'action' => 'admin.support.status_updated',
        ]);
    }

    public function test_customer_cannot_access_admin_support_inbox(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/support')->assertForbidden();
    }
}
