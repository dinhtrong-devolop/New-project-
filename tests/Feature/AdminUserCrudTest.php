<?php

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_create_and_edit_user_forms(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->actingAs($admin)->get('/admin/users/create')
            ->assertOk()
            ->assertSee('Thêm người dùng mới')
            ->assertSee('name="role"', false)
            ->assertSee('name="status"', false);

        $this->actingAs($admin)->get("/admin/users/{$customer->id}/edit")
            ->assertOk()
            ->assertSee('Sửa người dùng')
            ->assertSee($customer->email);
    }

    public function test_admin_can_create_user_with_wallet_and_cart(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New ShopKey Member',
            'email' => 'new-member@shopkey.test',
            'phone' => '0901234567',
            'role' => UserRole::Customer->value,
            'status' => 'active',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $user = User::query()->where('email', 'new-member@shopkey.test')->firstOrFail();

        $response->assertRedirect(route('admin.users.show', $user));
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertNull($user->locked_at);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('SecurePass123!', $user->password));
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'balance' => 0]);
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
    }

    public function test_admin_can_update_role_status_and_password(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['password' => 'OldSecure123!']);

        $this->actingAs($admin)->put("/admin/users/{$customer->id}", [
            'name' => 'ShopKey Staff',
            'email' => 'staff@shopkey.test',
            'phone' => '0912345678',
            'role' => UserRole::Staff->value,
            'status' => 'locked',
            'password' => 'NewSecure123!',
            'password_confirmation' => 'NewSecure123!',
        ])->assertRedirect(route('admin.users.show', $customer));

        $customer->refresh();
        $this->assertSame('ShopKey Staff', $customer->name);
        $this->assertSame(UserRole::Staff, $customer->role);
        $this->assertNotNull($customer->locked_at);
        $this->assertTrue(Hash::check('NewSecure123!', $customer->password));
    }

    public function test_admin_can_delete_unused_user_but_not_user_with_financial_history(): void
    {
        $admin = User::factory()->admin()->create();
        $unused = User::factory()->create();
        $unused->wallet()->create(['balance' => 0, 'currency' => 'VND']);
        $unused->cart()->create();

        $this->actingAs($admin)
            ->delete("/admin/users/{$unused->id}")
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $unused->id]);

        $customer = User::factory()->create();
        $wallet = $customer->wallet()->create(['balance' => 10000, 'currency' => 'VND']);
        WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'user_id' => $customer->id,
            'type' => WalletTransactionType::Deposit,
            'amount' => 10000,
            'balance_before' => 0,
            'balance_after' => 10000,
            'description' => 'Financial audit history',
            'status' => TransactionStatus::Completed,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/users/{$customer->id}")
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $customer->id]);
    }

    public function test_admin_cannot_delete_or_lock_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete("/admin/users/{$admin->id}")
            ->assertSessionHasErrors('user');

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'phone' => $admin->phone,
            'role' => UserRole::Customer->value,
            'status' => 'locked',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors('role');

        $admin->refresh();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertNull($admin->locked_at);
    }
}
