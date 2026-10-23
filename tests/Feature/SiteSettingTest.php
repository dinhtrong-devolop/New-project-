<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_general_settings_and_uploaded_brand_assets_are_applied(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/settings/general')
            ->assertOk()
            ->assertSee('Cài đặt chung')
            ->assertSee('Hình ảnh nhận diện')
            ->assertSee('Logo giao diện tối');

        $response = $this->actingAs($admin)->put('/admin/settings/general', [
            'site_name' => 'ShopKey Pro',
            'site_keywords' => 'key bản quyền, gift card, tài khoản số',
            'site_description' => 'Kho sản phẩm số ShopKey Pro giao tự động.',
            'contact_email' => 'hello@shopkey.test',
            'chat_label' => 'Chat với ShopKey AI',
            'chat_url' => 'https://example.com/chat',
            'footer_description' => 'Footer có thể chỉnh hoàn toàn trong quản trị.',
            'footer_trust_text' => '{site_name} bảo vệ mọi giao dịch',
            'footer_columns' => [
                ['title' => 'Cửa hàng', 'links' => [
                    ['label' => 'Sản phẩm', 'url' => '/products'],
                    ['label' => 'Giỏ hàng', 'url' => '/cart'],
                ]],
                ['title' => 'Thành viên', 'links' => [
                    ['label' => 'Tài khoản', 'url' => '/account'],
                    ['label' => 'Ví', 'url' => '/wallet'],
                ]],
                ['title' => 'Trợ giúp', 'links' => [
                    ['label' => 'Chat ngay', 'url' => 'https://example.com/chat'],
                    ['label' => 'Email', 'url' => 'mailto:hello@shopkey.test'],
                ]],
            ],
            'footer_copyright' => '© {year} {site_name}. Bảo lưu mọi quyền.',
            'footer_bottom_text' => 'Giao tự động · Hỗ trợ liên tục',
            'logo' => UploadedFile::fake()->image('logo.png', 500, 140),
            'dark_logo' => UploadedFile::fake()->image('dark-logo.png', 500, 140),
            'footer_logo' => UploadedFile::fake()->image('footer-logo.webp', 500, 140),
            'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
            'banner' => UploadedFile::fake()->image('banner.jpg', 1440, 360),
            'cover' => UploadedFile::fake()->image('cover.webp', 1200, 800),
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $setting = SiteSetting::query()->where('key', 'general')->firstOrFail();

        $this->assertSame('ShopKey Pro', $setting->site_name);
        $this->assertSame($admin->id, $setting->updated_by);
        Storage::disk('public')->assertExists($setting->logo_path);
        Storage::disk('public')->assertExists($setting->dark_logo_path);
        Storage::disk('public')->assertExists($setting->footer_logo_path);
        Storage::disk('public')->assertExists($setting->favicon_path);
        Storage::disk('public')->assertExists($setting->banner_path);
        Storage::disk('public')->assertExists($setting->cover_path);
        $this->assertDatabaseHas('activity_logs', [
            'actor_id' => $admin->id,
            'action' => 'admin.site_settings.updated',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ShopKey Pro - Chợ sản phẩm số chính hãng')
            ->assertSee('Kho sản phẩm số ShopKey Pro giao tự động.')
            ->assertSee('Footer có thể chỉnh hoàn toàn trong quản trị.')
            ->assertSee('ShopKey Pro bảo vệ mọi giao dịch')
            ->assertSee('Giao tự động · Hỗ trợ liên tục')
            ->assertSee('Chat ngay')
            ->assertSee('hello@shopkey.test')
            ->assertSee(Storage::disk('public')->url($setting->logo_path))
            ->assertSee(Storage::disk('public')->url($setting->dark_logo_path))
            ->assertSee(Storage::disk('public')->url($setting->banner_path));
    }

    public function test_admin_can_remove_custom_asset_and_return_to_default(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $oldLogo = UploadedFile::fake()->image('old-logo.png')->store('site-settings/logo', 'public');
        $oldDarkLogo = UploadedFile::fake()->image('old-dark-logo.png')->store('site-settings/dark_logo', 'public');
        $setting = SiteSetting::query()->create([
            'key' => 'general',
            ...SiteSetting::defaults(),
            'logo_path' => $oldLogo,
            'dark_logo_path' => $oldDarkLogo,
        ]);

        $this->actingAs($admin)->put('/admin/settings/general', [
            'site_name' => $setting->site_name,
            'site_keywords' => $setting->site_keywords,
            'site_description' => $setting->site_description,
            'contact_email' => $setting->contact_email,
            'chat_label' => $setting->chat_label,
            'chat_url' => '',
            'footer_description' => SiteSetting::defaults()['footer_description'],
            'footer_trust_text' => SiteSetting::defaults()['footer_trust_text'],
            'footer_columns' => SiteSetting::defaults()['footer_columns'],
            'footer_copyright' => SiteSetting::defaults()['footer_copyright'],
            'footer_bottom_text' => SiteSetting::defaults()['footer_bottom_text'],
            'remove_logo' => '1',
            'remove_dark_logo' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull($setting->fresh()->logo_path);
        $this->assertNull($setting->fresh()->dark_logo_path);
        Storage::disk('public')->assertMissing($oldLogo);
        Storage::disk('public')->assertMissing($oldDarkLogo);
    }

    public function test_footer_rejects_unsafe_javascript_link(): void
    {
        $admin = User::factory()->admin()->create();
        $footerColumns = SiteSetting::defaults()['footer_columns'];
        $footerColumns[0]['links'][0]['url'] = 'javascript:alert(1)';

        $this->actingAs($admin)->put('/admin/settings/general', [
            ...SiteSetting::defaults(),
            'footer_columns' => $footerColumns,
        ])->assertSessionHasErrors('footer_columns.0.links.0.url');

        $this->assertDatabaseCount('site_settings', 0);
    }
}
