<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\BankAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\User;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CompleteMobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_app_content_home_and_search_endpoints_work(): void
    {
        $product = Product::factory()->create(['name' => 'Office Premium Key', 'sold_count' => 25]);
        ProductInventory::factory()->for($product)->create();

        $this->getJson('/api/v1/app-config')->assertOk()->assertJsonPath('data.app.api_version', 'v1');
        $this->getJson('/api/v1/home')->assertOk()->assertJsonPath('data.featured_products.0.id', $product->id);
        $this->getJson('/api/v1/faqs')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/policies')->assertOk()->assertJsonCount(4, 'data');
        $this->getJson('/api/v1/pages/privacy')->assertOk()->assertJsonPath('data.slug', 'privacy');
        $this->getJson('/api/v1/search/suggestions?q=Office')->assertOk()->assertJsonPath('data.products.0.id', $product->id);
        $this->getJson('/api/v1/products/trending')->assertOk()->assertJsonPath('data.0.id', $product->id);

        $this->call('OPTIONS', '/api/v1/products', [], [], [], [
            'HTTP_ORIGIN' => 'https://mobile.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_password_sessions_and_device_management_work(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])->assertOk();
        Notification::assertSentTo($user, ResetPassword::class);

        $token = $user->createToken('Android phone')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)->getJson('/api/v1/sessions')->assertOk()->assertJsonPath('data.0.name', 'Android phone');
        $device = $this->withHeaders($headers)->postJson('/api/v1/devices', [
            'token' => 'firebase-device-token-123',
            'platform' => 'android',
            'device_name' => 'Pixel',
        ])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('device_tokens', ['id' => $device, 'user_id' => $user->id]);
        $this->withHeaders($headers)->deleteJson("/api/v1/devices/{$device}")->assertOk();

        $this->withHeaders($headers)->putJson('/api/v1/change-password', [
            'current_password' => 'password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertOk();
        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));

        $secondaryId = $user->createToken('Tablet')->accessToken->id;
        $this->withHeaders($headers)->deleteJson("/api/v1/sessions/{$secondaryId}")->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $secondaryId]);

        $user->createToken('Browser');
        $this->withHeaders($headers)->postJson('/api/v1/logout-all')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_password_can_be_reset_with_a_valid_broker_token(): void
    {
        $user = User::factory()->create();
        $resetToken = Password::broker()->createToken($user);
        $user->createToken('old-device');

        $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => $resetToken,
            'password' => 'reset-secure-password',
            'password_confirmation' => 'reset-secure-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('reset-secure-password', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_email_verification_can_be_resent_and_completed_from_a_signed_link(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/email/verification-notification')->assertOk();
        Notification::assertSentTo($user, VerifyEmail::class);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);
        $this->getJson($url)->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_notifications_are_private_and_can_be_marked_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->order($user, OrderStatus::Completed, PaymentStatus::Paid);
        $otherOrder = $this->order($other, OrderStatus::Completed, PaymentStatus::Paid);
        $user->notify(new OrderCompletedNotification($order));
        $other->notify(new OrderCompletedNotification($otherOrder));

        $notificationId = $user->notifications()->value('id');
        $otherNotificationId = $other->notifications()->value('id');

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications')
            ->assertOk()->assertJsonPath('meta.unread', 1)->assertJsonMissing(['id' => $otherNotificationId]);
        $this->actingAs($user, 'sanctum')->patchJson("/api/v1/notifications/{$notificationId}/read")->assertOk();
        $this->assertNotNull($user->notifications()->whereKey($notificationId)->firstOrFail()->read_at);
        $this->actingAs($user, 'sanctum')->patchJson("/api/v1/notifications/{$otherNotificationId}/read")->assertNotFound();
    }

    public function test_bank_deposit_api_enforces_ownership_and_can_cancel_pending_invoice(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $account = BankAccount::query()->create([
            'webhook_key' => 'BANKWEBHOOKKEY000000000001',
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => '0123456789',
            'account_holder' => 'SHOP KEY',
            'deposit_prefix' => 'SKN',
            'minimum_deposit' => 10000,
            'is_visible' => true,
            'auto_confirm' => true,
        ]);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/bank-accounts')->assertOk()->assertJsonPath('data.0.id', $account->id);
        $depositId = $this->actingAs($user, 'sanctum')->postJson('/api/v1/deposits/bank', [
            'bank_account_id' => $account->id,
            'amount' => 50000,
        ])->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/deposits/bank')->assertOk()->assertJsonPath('data.0.id', $depositId);
        $this->actingAs($other, 'sanctum')->getJson("/api/v1/deposits/bank/{$depositId}")->assertNotFound();
        $this->actingAs($user, 'sanctum')->getJson("/api/v1/deposits/bank/{$depositId}/status")->assertOk();
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/deposits/bank/{$depositId}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_support_wishlist_recent_views_and_payment_methods_work(): void
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 250000, 'currency' => 'VND']);
        $product = Product::factory()->create();
        ProductInventory::factory()->for($product)->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson("/api/v1/wishlist/{$product->slug}")->assertCreated();
        $this->withToken($token)->getJson('/api/v1/wishlist')->assertOk()->assertJsonPath('data.0.id', $product->id);
        $this->withToken($token)->getJson("/api/v1/products/{$product->slug}")->assertOk();
        $this->withToken($token)->getJson('/api/v1/products/recently-viewed')->assertOk()->assertJsonPath('data.0.id', $product->id);
        $this->withToken($token)->getJson('/api/v1/payment-methods')->assertOk()->assertJsonPath('data.checkout.0.balance', 250000);

        $this->withToken($token)->postJson('/api/v1/support/messages', ['message' => 'Tôi cần hỗ trợ đơn hàng.'])->assertCreated();
        $this->withToken($token)->getJson('/api/v1/support/messages')->assertOk()->assertJsonPath('data.messages.0.mine', true);
        $this->withToken($token)->postJson('/api/v1/support/read')->assertOk();
        $this->withToken($token)->deleteJson("/api/v1/wishlist/{$product->slug}")->assertOk();
    }

    public function test_order_actions_reject_unsafe_cancel_and_accept_refund_request(): void
    {
        $user = User::factory()->create();
        $completed = $this->order($user, OrderStatus::Completed, PaymentStatus::Paid);
        $pending = $this->order($user, OrderStatus::Pending, PaymentStatus::Pending);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/orders/{$completed->order_code}/cancel")->assertUnprocessable();
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/orders/{$completed->order_code}/refund-request", [
            'reason' => 'Sản phẩm không hoạt động đúng như mô tả.',
        ])->assertCreated()->assertJsonPath('data.status', 'pending');
        $this->actingAs($user, 'sanctum')->getJson("/api/v1/orders/{$completed->order_code}")->assertOk()->assertJsonPath('data.refund_request.status', 'pending');
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/orders/{$pending->order_code}/cancel")->assertOk()->assertJsonPath('data.order_status', 'cancelled');
    }

    public function test_admin_can_review_a_mobile_refund_request(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = $this->order($user, OrderStatus::Completed, PaymentStatus::Paid);
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/orders/{$order->order_code}/refund-request", [
            'reason' => 'Sản phẩm không hoạt động đúng như mô tả.',
        ])->assertCreated();

        $this->actingAs($admin)->get("/admin/orders/{$order->order_code}")
            ->assertOk()->assertSee('YÊU CẦU HOÀN TIỀN')->assertSee('Sản phẩm không hoạt động đúng như mô tả.');
        $this->actingAs($admin)->patch("/admin/orders/{$order->order_code}/refund-request", [
            'action' => 'reject',
            'admin_note' => 'Cần bổ sung bằng chứng lỗi.',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('order_refund_requests', ['order_id' => $order->id, 'status' => 'rejected']);

        $approvedOrder = $this->order($user, OrderStatus::Completed, PaymentStatus::Paid, 75000);
        $this->actingAs($user, 'sanctum')->postJson("/api/v1/orders/{$approvedOrder->order_code}/refund-request", [
            'reason' => 'Key đã giao không thể kích hoạt trên thiết bị.',
        ])->assertCreated();
        $this->actingAs($admin)->patch("/admin/orders/{$approvedOrder->order_code}/refund-request", [
            'action' => 'approve',
            'admin_note' => 'Đã xác minh lỗi sản phẩm.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Refunded, $approvedOrder->fresh()->payment_status);
        $this->assertSame(75000, $user->wallet->fresh()->balance);
        $this->assertDatabaseHas('order_refund_requests', ['order_id' => $approvedOrder->id, 'status' => 'approved']);
        $this->actingAs($admin)->patch("/admin/orders/{$approvedOrder->order_code}/refund-request", ['action' => 'approve'])->assertNotFound();
        $this->assertSame(75000, $user->wallet->fresh()->balance);
    }

    public function test_failed_order_can_be_paid_again_and_delivered_atomically(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 500000, 'currency' => 'VND']);
        $product = Product::factory()->create(['price' => 120000, 'sold_count' => 0]);
        $inventory = ProductInventory::factory()->for($product)->create();
        $order = $this->order($user, OrderStatus::Pending, PaymentStatus::Failed, 120000);
        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'product_type' => $product->product_type,
            'unit_price' => 120000,
            'quantity' => 1,
            'subtotal' => 120000,
            'warranty_days' => 30,
            'delivery_status' => DeliveryStatus::Failed,
        ]);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/orders/{$order->order_code}/retry-payment")
            ->assertOk()->assertJsonPath('data.payment_status', 'paid')->assertJsonPath('data.items.0.delivery_status', 'delivered');

        $this->assertSame(380000, $user->wallet->fresh()->balance);
        $this->assertSame('sold', $inventory->fresh()->status->value);
        $this->assertDatabaseHas('order_deliveries', ['order_item_id' => $item->id, 'product_inventory_id' => $inventory->id]);
    }

    public function test_review_listing_update_and_delete_are_owner_safe(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create();
        $order = $this->order($user, OrderStatus::Completed, PaymentStatus::Paid);
        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'product_type' => $product->product_type,
            'unit_price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
            'warranty_days' => 30,
            'delivery_status' => DeliveryStatus::Delivered,
        ]);

        $reviewId = $this->actingAs($user, 'sanctum')->postJson('/api/v1/reviews', [
            'order_item_id' => $item->id,
            'rating' => 5,
            'comment' => 'Sản phẩm rất tốt.',
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/v1/products/{$product->slug}/reviews")->assertOk()->assertJsonPath('data.0.id', $reviewId);
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/reviews/mine')->assertOk()->assertJsonPath('data.0.product.id', $product->id);
        $this->actingAs($other, 'sanctum')->putJson("/api/v1/reviews/{$reviewId}", ['rating' => 1])->assertNotFound();
        $this->actingAs($user, 'sanctum')->putJson("/api/v1/reviews/{$reviewId}", ['rating' => 4, 'comment' => 'Đã cập nhật.'])->assertOk();
        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/reviews/{$reviewId}")->assertOk();
        $this->assertSame(0, $product->fresh()->rating_count);
    }

    private function order(User $user, OrderStatus $status, PaymentStatus $payment, int $total = 100000): Order
    {
        return Order::query()->create([
            'order_code' => 'SK'.strtoupper(fake()->unique()->bothify('########??')),
            'user_id' => $user->id,
            'subtotal' => $total,
            'discount_amount' => 0,
            'total' => $total,
            'payment_method' => 'wallet',
            'payment_status' => $payment,
            'order_status' => $status,
            'paid_at' => $payment === PaymentStatus::Paid ? now() : null,
            'completed_at' => $status === OrderStatus::Completed ? now() : null,
        ]);
    }
}
