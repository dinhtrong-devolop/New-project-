<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\SupportMessage;
use App\Services\CartService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $view->with('siteSettings', SiteSetting::current());
        });

        View::composer('layouts.storefront', function ($view): void {
            $view->with('cartItemCount', app(CartService::class)->itemCount(request()));
            $view->with('notificationCount', auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
            $view->with('supportUnreadCount', auth()->check() ? SupportMessage::query()
                ->whereNull('read_at')
                ->where('sender_id', '!=', auth()->id())
                ->whereHas('conversation', fn ($query) => $query->where('user_id', auth()->id()))
                ->count() : 0);
        });

        View::composer(['layouts.admin', 'layouts.storefront'], function ($view): void {
            $view->with('supportAdminUnreadCount', auth()->user()?->role === UserRole::Admin ? SupportMessage::query()
                ->whereNull('read_at')
                ->whereHas('conversation', fn ($query) => $query
                    ->whereColumn('support_messages.sender_id', 'support_conversations.user_id'))
                ->count() : 0);
        });
    }
}
