<?php

namespace Tests\Feature;

use App\Enums\DeliveryType;
use App\Enums\InventoryStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\PaymentGatewaySetting;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_area_requires_authentication_and_admin_role(): void
    {
        $this->get('/admin')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard vận hành');
    }

    public function test_admin_can_create_product_and_import_encrypted_inventory(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Admin Test',
            'slug' => 'admin-test',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)->post('/admin/products', [
            'category_id' => $category->id,
            'name' => 'Admin Managed Product',
            'slug' => 'admin-managed-product',
            'sku' => 'ADMIN-SKU-001',
            'product_type' => ProductType::LicenseKey->value,
            'price' => 199000,
            'status' => ProductStatus::Active->value,
            'delivery_type' => DeliveryType::Automatic->value,
            'warranty_days' => 30,
            'is_featured' => '1',
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'ADMIN-SKU-001')->firstOrFail();

        $this->actingAs($admin)->post('/admin/inventory', [
            'product_id' => $product->id,
            'inventory' => "ADMIN-KEY-001\nADMIN-KEY-002",
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $product->inventories()->count());
        $storedPayload = DB::table('product_inventories')->where('product_id', $product->id)->value('encrypted_payload');
        $this->assertStringNotContainsString('ADMIN-KEY-001', $storedPayload);
    }

    public function test_admin_can_delete_available_inventory_with_an_audit_log(): void
    {
        $admin = User::factory()->admin()->create();
        $inventory = ProductInventory::factory()->create([
            'status' => InventoryStatus::Available,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inventory.index', ['status' => InventoryStatus::Available->value]))
            ->assertOk()
            ->assertSee('Phân loại kho theo trạng thái')
            ->assertSee('Xóa vĩnh viễn mục kho');

        $this->actingAs($admin)
            ->delete(route('admin.inventory.destroy', $inventory))
            ->assertRedirect()
            ->assertSessionHas('status', 'Đã xóa mục kho an toàn.');

        $this->assertDatabaseMissing('product_inventories', ['id' => $inventory->id]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'admin.inventory.deleted',
            'subject_id' => $inventory->id,
        ]);
    }

    public function test_admin_cannot_delete_reserved_or_sold_inventory(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([InventoryStatus::Reserved, InventoryStatus::Sold] as $status) {
            $inventory = ProductInventory::factory()->create(['status' => $status]);

            $this->actingAs($admin)
                ->delete(route('admin.inventory.destroy', $inventory))
                ->assertUnprocessable();

            $this->assertDatabaseHas('product_inventories', ['id' => $inventory->id]);
        }
    }

    public function test_admin_can_bulk_delete_only_eligible_inventory_atomically(): void
    {
        $admin = User::factory()->admin()->create();
        $available = ProductInventory::factory()->create(['status' => InventoryStatus::Available]);
        $disabled = ProductInventory::factory()->create(['status' => InventoryStatus::Disabled]);

        $this->actingAs($admin)
            ->delete(route('admin.inventory.bulk-destroy'), [
                'inventory_ids' => [$available->id, $disabled->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Đã xóa an toàn 2 mục kho.');

        $this->assertDatabaseMissing('product_inventories', ['id' => $available->id]);
        $this->assertDatabaseMissing('product_inventories', ['id' => $disabled->id]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'admin.inventory.bulk_deleted',
        ]);

        $eligible = ProductInventory::factory()->create(['status' => InventoryStatus::Available]);
        $protected = ProductInventory::factory()->create(['status' => InventoryStatus::Reserved]);

        $this->actingAs($admin)
            ->delete(route('admin.inventory.bulk-destroy'), [
                'inventory_ids' => [$eligible->id, $protected->id],
            ])
            ->assertSessionHasErrors('inventory_ids');

        $this->assertDatabaseHas('product_inventories', ['id' => $eligible->id]);
        $this->assertDatabaseHas('product_inventories', ['id' => $protected->id]);
    }

    public function test_admin_can_delete_a_product_without_archive_action(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $deletedProduct = Product::factory()->create();
        $imagePath = 'products/delete-me.jpg';

        Storage::disk('public')->put($imagePath, 'image contents');
        $deletedProduct->images()->create([
            'path' => $imagePath,
            'alt_text' => $deletedProduct->name,
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $this->assertFalse(Route::has('admin.products.archive'));
        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('Lưu trữ');

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $deletedProduct))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('products', ['id' => $deletedProduct->id]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_admin_cannot_delete_a_product_with_inventory(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        ProductInventory::factory()->for($product)->create();

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertSessionHasErrors('product');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_admin_can_lock_customer_and_adjust_wallet_with_ledger(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $customer->wallet()->create(['balance' => 0, 'currency' => 'VND']);

        $this->actingAs($admin)
            ->patch("/admin/users/{$customer->id}/lock")
            ->assertSessionHasNoErrors();

        $this->assertNotNull($customer->fresh()->locked_at);

        $this->actingAs($admin)->post("/admin/users/{$customer->id}/wallet-adjustment", [
            'amount' => 150000,
            'reason' => 'Điều chỉnh kiểm thử quản trị',
        ])->assertSessionHasNoErrors();

        $this->assertSame(150000, $customer->wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $customer->id,
            'type' => 'admin_adjustment',
            'amount' => 150000,
            'balance_after' => 150000,
        ]);
    }

    public function test_admin_can_create_and_toggle_voucher(): void
    {
        $admin = User::factory()->admin()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post('/admin/vouchers', [
            'code' => 'ADMIN20',
            'type' => 'percentage',
            'discount_value' => 20,
            'minimum_order_amount' => 100000,
            'maximum_discount' => 50000,
            'per_user_usage_limit' => 1,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $voucherId = DB::table('vouchers')->where('code', 'ADMIN20')->value('id');

        $this->actingAs($admin)
            ->patch("/admin/vouchers/{$voucherId}/toggle")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vouchers', ['id' => $voucherId, 'is_active' => false]);
    }

    public function test_admin_can_securely_configure_card_deposit_gateway(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/deposits/settings')
            ->assertOk()
            ->assertSee('/api/payment/naptudong/callback')
            ->assertSee('Cài đặt cổng nạp thẻ');

        $this->actingAs($admin)->put('/admin/deposits/settings', [
            'is_enabled' => '1',
            'provider_url' => 'https://gateway.example/chargingws/v2/',
            'discount_percent' => '20',
            'partner_id' => 'partner-admin-test',
            'partner_key' => 'partner-secret-admin-test',
        ])->assertRedirect(route('admin.deposits.settings.edit'))->assertSessionHasNoErrors();

        $setting = PaymentGatewaySetting::query()->where('gateway', 'naptudong')->firstOrFail();

        $this->assertTrue($setting->is_enabled);
        $this->assertSame('https://gateway.example/chargingws/v2', $setting->provider_url);
        $this->assertSame('20.00', $setting->discount_percent);
        $this->assertSame('partner-secret-admin-test', $setting->partner_key);
        $this->assertStringNotContainsString(
            'partner-secret-admin-test',
            (string) DB::table('payment_gateway_settings')->where('id', $setting->id)->value('partner_key'),
        );
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $admin->id,
            'action' => 'admin.payment_gateway.updated',
        ]);
    }
}
