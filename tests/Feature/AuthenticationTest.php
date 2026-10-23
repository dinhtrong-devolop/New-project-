<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_authentication_pages_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Chào mừng bạn trở lại');
        $this->get('/register')->assertOk()->assertSee('Bắt đầu với ShopKey');
        $this->get('/forgot-password')->assertOk()->assertSee('Quên mật khẩu?');
    }

    public function test_customer_can_register_with_a_wallet_and_cart(): void
    {
        $response = $this->post('/register', [
            'name' => 'Khách ShopKey',
            'email' => 'customer@shopkey.test',
            'phone' => '0901234567',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ]);

        $user = User::query()->where('email', 'customer@shopkey.test')->firstOrFail();

        $response->assertRedirect(route('account.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('SecurePass123!', $user->password));
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'balance' => 0]);
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
    }

    public function test_customer_can_login_with_remember_me_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'SecurePass123!']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'SecurePass123!',
            'remember' => '1',
        ])->assertRedirect(route('account.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_locked_customer_cannot_login(): void
    {
        $user = User::factory()->locked()->create(['password' => 'SecurePass123!']);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'SecurePass123!',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_customer_can_request_and_use_a_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors();

        $token = null;

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewSecurePass123!', $user->fresh()->password));
    }

    public function test_customer_can_update_profile_and_change_password(): void
    {
        $user = User::factory()->create(['password' => 'SecurePass123!']);

        $this->actingAs($user)->patch('/account/profile', [
            'name' => 'ShopKey Member',
            'email' => 'member@shopkey.test',
            'phone' => '0912345678',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'ShopKey Member',
            'email' => 'member@shopkey.test',
        ]);

        $this->actingAs($user->fresh())->put('/account/password', [
            'current_password' => 'SecurePass123!',
            'password' => 'ChangedPass123!',
            'password_confirmation' => 'ChangedPass123!',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('ChangedPass123!', $user->fresh()->password));
    }
}
