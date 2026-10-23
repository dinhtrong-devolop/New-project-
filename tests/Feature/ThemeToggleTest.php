<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_and_auth_pages_include_the_persistent_theme_control(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('shopkey-theme', false)
            ->assertSee('data-theme-toggle', false)
            ->assertSee('theme-toggle--mobile-nav', false)
            ->assertSee('theme-toggle--drawer', false)
            ->assertSee('data-mobile-menu-toggle', false)
            ->assertSee('aria-controls="mobile-navigation"', false)
            ->assertSee('id="mobile-navigation"', false);

        $this->get('/login')
            ->assertOk()
            ->assertSee('shopkey-theme', false)
            ->assertSee('data-theme-toggle', false);
    }

    public function test_admin_pages_include_the_theme_control(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('shopkey-theme', false)
            ->assertSee('data-theme-toggle', false);
    }
}
