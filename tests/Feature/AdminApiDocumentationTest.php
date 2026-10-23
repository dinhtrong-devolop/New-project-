<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_documentation_is_restricted_to_admins(): void
    {
        $this->get('/admin/api-docs')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/admin/api-docs')
            ->assertForbidden();
    }

    public function test_admin_can_view_app_api_instructions(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/api-docs')
            ->assertOk()
            ->assertSee('Hướng dẫn API App')
            ->assertSee('/api/v1/login')
            ->assertSee('/api/v1/products')
            ->assertSee('/api/v1/orders')
            ->assertSee('/api/v1/app-config')
            ->assertSee('/api/v1/forgot-password')
            ->assertSee('/api/v1/deposits/bank')
            ->assertSee('/api/v1/support/messages')
            ->assertSee('/api/v1/wishlist')
            ->assertSee('63')
            ->assertSee('Authorization: Bearer YOUR_ACCESS_TOKEN')
            ->assertSee('Sao chép URL gốc');
    }
}
