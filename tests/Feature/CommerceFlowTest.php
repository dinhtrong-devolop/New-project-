<?php

namespace Tests\Feature;

use App\Enums\InventoryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_checkout_and_receive_unique_digital_inventory(): void
    {
        $user = $this->customerWithWallet(1_000_000);
        $product = Product::factory()->create(['price' => 120_000]);
        ProductInventory::factory()->count(2)->for($product)->create();

        $this->actingAs($user)
            ->post('/cart', ['product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect();

        $response = $this->post('/checkout', ['payment_method' => 'wallet']);
        $order = $user->orders()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame(760_000, $user->wallet->fresh()->balance);
        $this->assertSame(OrderStatus::Completed, $order->order_status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertDatabaseCount('order_deliveries', 2);
        $this->assertSame(2, ProductInventory::query()->where('status', InventoryStatus::Sold->value)->count());
        $this->assertSame(2, ProductInventory::query()->whereHas('delivery')->distinct()->count());
        $this->assertDatabaseHas('wallet_transactions', ['amount' => -240_000, 'reference_id' => $order->id]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $user->cart->id]);
    }

    public function test_checkout_rolls_back_when_wallet_is_insufficient(): void
    {
        $user = $this->customerWithWallet(10_000);
        $product = Product::factory()->create(['price' => 120_000]);
        $inventory = ProductInventory::factory()->for($product)->create();
        $user->cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($user)
            ->from('/checkout')
            ->post('/checkout', ['payment_method' => 'wallet'])
            ->assertRedirect('/checkout')
            ->assertSessionHasErrors('wallet');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10_000, $user->wallet->fresh()->balance);
        $this->assertSame(InventoryStatus::Available, $inventory->fresh()->status);
    }

    public function test_checkout_rolls_back_when_inventory_changed_after_cart_was_created(): void
    {
        $user = $this->customerWithWallet(1_000_000);
        $product = Product::factory()->create(['price' => 120_000]);
        $inventory = ProductInventory::factory()->for($product)->create();
        $user->cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        $inventory->update(['status' => InventoryStatus::Disabled]);

        $this->actingAs($user)
            ->from('/checkout')
            ->post('/checkout', ['payment_method' => 'wallet'])
            ->assertRedirect('/checkout')
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1_000_000, $user->wallet->fresh()->balance);
    }

    public function test_only_a_customer_with_completed_purchase_can_review_once(): void
    {
        $user = $this->customerWithWallet(500_000);
        $product = Product::factory()->create(['price' => 100_000]);
        ProductInventory::factory()->for($product)->create();
        $user->cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($user)->post('/checkout', ['payment_method' => 'wallet']);
        $item = OrderItem::query()->firstOrFail();

        $this->post('/reviews', ['order_item_id' => $item->id, 'rating' => 5, 'comment' => 'Giao key rất nhanh.'])
            ->assertRedirect();
        $this->assertDatabaseHas('reviews', ['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 5]);

        $this->post('/reviews', ['order_item_id' => $item->id, 'rating' => 4])
            ->assertSessionHasErrors();
        $this->assertDatabaseCount('reviews', 1);
    }

    private function customerWithWallet(int $balance): User
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => $balance, 'currency' => 'VND']);
        $user->cart()->create();

        return $user->load(['wallet', 'cart']);
    }
}
