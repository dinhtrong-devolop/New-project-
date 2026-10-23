<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    public function test_homepage_renders_the_shopkey_marketplace(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('ShopKey')
            ->assertSee('Chợ sản phẩm số')
            ->assertSee('Windows 11 Pro License')
            ->assertSee('Sản phẩm nổi bật');
    }

    public function test_catalog_can_be_filtered_by_category(): void
    {
        $this->get('/products?category=game-keys')
            ->assertOk()
            ->assertSee('Nebula Quest PC Game Key')
            ->assertDontSee('Office Home &amp; Business 2024', false);
    }

    public function test_product_detail_page_renders_from_slug_binding(): void
    {
        $this->get('/products/windows-11-pro-license')
            ->assertOk()
            ->assertSee('Windows 11 Pro License')
            ->assertSee('Thêm vào giỏ');
    }

    public function test_cart_checkout_and_protected_account_render(): void
    {
        $this->get('/cart')->assertOk()->assertSee('Giỏ hàng');
        $this->get('/checkout')->assertOk()->assertSee('Thanh toán an toàn');
        $this->get('/account')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/account')
            ->assertOk()
            ->assertSee('Trung tâm tài khoản');
    }
}
