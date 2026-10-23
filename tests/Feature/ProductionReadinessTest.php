<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\WalletTransactionType;
use App\Models\Category;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_in_inactive_categories_are_hidden_and_cannot_be_purchased(): void
    {
        $category = Category::query()->create([
            'name' => 'Danh mục tạm tắt',
            'slug' => 'danh-muc-tam-tat',
            'is_active' => false,
            'sort_order' => 1,
        ]);
        $product = Product::factory()->for($category)->create([
            'name' => 'Sản phẩm không công khai',
            'slug' => 'san-pham-khong-cong-khai',
            'status' => ProductStatus::Active,
        ]);
        ProductInventory::factory()->for($product)->create();

        $this->get('/products')->assertOk()->assertDontSee($product->name);
        $this->get('/products/'.$product->slug)->assertNotFound();
        $this->getJson('/api/v1/products')->assertOk()->assertJsonMissing(['id' => $product->id]);
        $this->getJson('/api/v1/products/'.$product->slug)->assertNotFound();

        $user = User::factory()->create();
        $user->cart()->create();
        $this->actingAs($user)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('product');
    }

    public function test_admin_cannot_create_a_category_parent_cycle(): void
    {
        $admin = User::factory()->admin()->create();
        $root = $this->category('Gốc', 'goc');
        $child = $this->category('Con', 'con', $root->id);
        $grandchild = $this->category('Cháu', 'chau', $child->id);

        $this->actingAs($admin)->put('/admin/categories/'.$root->id, [
            'parent_id' => $grandchild->id,
            'name' => $root->name,
            'slug' => $root->slug,
            'sort_order' => 1,
            'is_active' => '1',
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_admin_cannot_change_product_type_after_inventory_exists(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['product_type' => ProductType::LicenseKey]);
        ProductInventory::factory()->for($product)->create(['inventory_type' => ProductType::LicenseKey]);

        $this->actingAs($admin)->put('/admin/products/'.$product->id, [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'product_type' => ProductType::Account->value,
            'price' => $product->price,
            'status' => $product->status->value,
            'delivery_type' => $product->delivery_type->value,
            'warranty_days' => $product->warranty_days,
        ])->assertSessionHasErrors('product_type');

        $this->assertSame(ProductType::LicenseKey, $product->fresh()->product_type);
    }

    public function test_delivered_and_refunded_orders_cannot_be_moved_back_to_processing(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 500_000, 'currency' => 'VND']);
        $cart = $user->cart()->create();
        $product = Product::factory()->create(['price' => 100_000]);
        ProductInventory::factory()->for($product)->create();
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        $order = app(CheckoutService::class)->checkout($user);

        $this->actingAs($admin)->patch('/admin/orders/'.$order->order_code, [
            'order_status' => OrderStatus::Processing->value,
            'payment_status' => PaymentStatus::Paid->value,
        ])->assertSessionHasErrors('order_status');

        $this->actingAs($admin)->patch('/admin/orders/'.$order->order_code, [
            'order_status' => OrderStatus::Refunded->value,
            'payment_status' => PaymentStatus::Paid->value,
            'admin_note' => 'Hoàn tiền kiểm thử an toàn',
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Refunded, $order->order_status);
        $this->assertSame(PaymentStatus::Refunded, $order->payment_status);
        $this->assertSame(500_000, $user->wallet->fresh()->balance);

        $this->actingAs($admin)->patch('/admin/orders/'.$order->order_code, [
            'order_status' => OrderStatus::Completed->value,
            'payment_status' => PaymentStatus::Refunded->value,
        ])->assertSessionHasErrors('order_status');

        $this->assertSame(OrderStatus::Refunded, $order->fresh()->order_status);
    }

    public function test_wallet_idempotency_key_never_changes_the_balance_twice(): void
    {
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 0, 'currency' => 'VND']);
        $wallets = app(WalletService::class);

        $first = $wallets->change($user, 100_000, WalletTransactionType::Deposit, 'production-idempotency', 'Lần đầu');
        $second = $wallets->change($user, 999_000, WalletTransactionType::Deposit, 'production-idempotency', 'Lần lặp');

        $this->assertTrue($first->is($second));
        $this->assertSame(100_000, $user->wallet->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_api_rejects_invalid_catalog_filters(): void
    {
        $this->getJson('/api/v1/products?type=invalid&per_page=999&min_price=100&max_price=10')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'per_page', 'max_price']);

        $this->getJson('/api/v1/products?max_price=100000')->assertOk();
    }

    public function test_payment_gateway_secret_is_never_serialized(): void
    {
        $setting = PaymentGatewaySetting::query()->create([
            'gateway' => 'naptudong',
            'is_enabled' => true,
            'provider_url' => 'https://gateway.example.test',
            'discount_percent' => 0,
            'partner_id' => 'partner-id',
            'partner_key' => 'top-secret-partner-key',
        ]);

        $this->assertArrayNotHasKey('partner_key', $setting->toArray());
    }

    public function test_guest_cart_merge_clamps_quantity_to_available_stock(): void
    {
        $product = Product::factory()->create();
        ProductInventory::factory()->count(5)->for($product)->create();

        $this->post('/cart', ['product_id' => $product->id, 'quantity' => 4])->assertSessionHasNoErrors();

        $user = User::factory()->create();
        $cart = $user->cart()->create();
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 4]);

        $this->actingAs($user)->get('/cart')->assertOk();

        $this->assertSame(5, $cart->items()->where('product_id', $product->id)->value('quantity'));
    }

    private function category(string $name, string $slug, ?int $parentId = null): Category
    {
        return Category::query()->create([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
