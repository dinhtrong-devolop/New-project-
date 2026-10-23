<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_uses_consistent_unauthenticated_and_catalog_responses(): void
    {
        $this->getJson('/api/v1/cart')
            ->assertUnauthorized()
            ->assertExactJson(['success' => false, 'message' => 'Unauthenticated', 'errors' => []]);

        $this->seed(CatalogSeeder::class);
        $this->getJson('/api/v1/products?per_page=5')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Products retrieved successfully')
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure(['data' => [['id', 'slug', 'price', 'stock']], 'meta' => ['current_page', 'last_page', 'total']]);
    }

    public function test_mobile_user_can_login_manage_cart_checkout_and_open_delivery(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $user->wallet()->create(['balance' => 500_000, 'currency' => 'VND']);
        $user->cart()->create();
        $product = Product::factory()->create(['price' => 100_000]);
        ProductInventory::factory()->for($product)->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit-phone',
        ])->assertOk()->assertJsonStructure(['data' => ['user', 'token', 'token_type']]);

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 1])
            ->assertCreated()->assertJsonPath('data.total', 100_000);

        $checkout = $this->postJson('/api/v1/checkout', ['payment_method' => 'wallet'])
            ->assertCreated()
            ->assertJsonPath('data.order_status', 'completed')
            ->assertJsonPath('data.items.0.deliveries.0.credentials.key', ProductInventory::query()->first()->encrypted_payload['key']);

        $code = $checkout->json('data.order_code');
        $this->getJson('/api/v1/orders/'.$code)
            ->assertOk()
            ->assertJsonPath('data.items.0.deliveries.0.credentials.key', ProductInventory::query()->first()->encrypted_payload['key']);
        $this->assertSame(400_000, $user->wallet->fresh()->balance);
    }
}
