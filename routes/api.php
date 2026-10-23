<?php

use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankDepositController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\NaptudongCallbackController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderActionController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SepayWebhookController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/payment/naptudong/callback', NaptudongCallbackController::class)
    ->middleware('throttle:120,1')
    ->name('payment.naptudong.callback');

Route::post('/payment/sepay/webhook/{bankAccount:webhook_key}', SepayWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('payment.sepay.webhook');

Route::get('/v1/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])->whereNumber('id')->name('verification.verify');

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::middleware('throttle:10,1')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/forgot-password', [PasswordController::class, 'forgot'])->name('password.forgot');
        Route::post('/reset-password', [PasswordController::class, 'reset'])->name('password.reset');
    });
    Route::middleware('throttle:60,1')->group(function (): void {
        Route::get('/app-config', [AppController::class, 'config'])->name('app-config');
        Route::get('/home', [AppController::class, 'home'])->name('home');
        Route::get('/pages/{slug}', [ContentController::class, 'page'])->name('pages.show');
        Route::get('/faqs', [ContentController::class, 'faqs'])->name('faqs.index');
        Route::get('/policies', [ContentController::class, 'policies'])->name('policies.index');
        Route::get('/categories', [CatalogController::class, 'categories'])->name('categories.index');
        Route::get('/products', [CatalogController::class, 'products'])->name('products.index');
        Route::get('/products/trending', [SearchController::class, 'trending'])->name('products.trending');
        Route::get('/products/{product:slug}/reviews', [ReviewController::class, 'index'])->name('products.reviews.index');
        Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');
        Route::get('/products/recently-viewed', [SearchController::class, 'recentlyViewed'])
            ->middleware(['auth:sanctum', 'active'])->name('products.recently-viewed');
        Route::get('/products/{product:slug}', [CatalogController::class, 'show'])->name('products.show');
    });

    Route::middleware(['auth:sanctum', 'active', 'throttle:60,1'])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [SessionController::class, 'destroyAll'])->name('logout-all');
        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::delete('/sessions/{token}', [SessionController::class, 'destroy'])->whereNumber('token')->name('sessions.destroy');
        Route::put('/change-password', [PasswordController::class, 'change'])->name('password.change');
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')->name('verification.send');
        Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
        Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
        Route::post('/cart/voucher', [CartController::class, 'applyVoucher'])->name('cart.voucher.store');
        Route::delete('/cart/voucher', [CartController::class, 'removeVoucher'])->name('cart.voucher.destroy');

        Route::post('/checkout', CheckoutController::class)->name('checkout');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/cancel', [OrderActionController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/retry-payment', [OrderActionController::class, 'retryPayment'])->name('orders.retry-payment');
        Route::post('/orders/{order}/refund-request', [OrderActionController::class, 'requestRefund'])->name('orders.refund-request');
        Route::get('/payment-methods', PaymentMethodController::class)->name('payment-methods.index');
        Route::get('/reviews/mine', [ReviewController::class, 'mine'])->name('reviews.mine');
        Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
        Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/{product:slug}', [WishlistController::class, 'store'])->name('wishlist.store');
        Route::delete('/wishlist/{product:slug}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

        Route::get('/wallet', [WalletController::class, 'show'])->name('wallet.show');
        Route::get('/wallet/transactions', [WalletController::class, 'transactions'])->name('wallet.transactions');
        Route::post('/deposits/card', [DepositController::class, 'store'])->name('deposits.store');
        Route::get('/deposits', [DepositController::class, 'index'])->name('deposits.index');
        Route::get('/bank-accounts', [BankDepositController::class, 'accounts'])->name('bank-accounts.index');
        Route::get('/deposits/bank', [BankDepositController::class, 'index'])->name('bank-deposits.index');
        Route::post('/deposits/bank', [BankDepositController::class, 'store'])->name('bank-deposits.store');
        Route::get('/deposits/bank/{bankDeposit}', [BankDepositController::class, 'show'])->name('bank-deposits.show');
        Route::get('/deposits/bank/{bankDeposit}/status', [BankDepositController::class, 'status'])->name('bank-deposits.status');
        Route::post('/deposits/bank/{bankDeposit}/cancel', [BankDepositController::class, 'cancel'])->name('bank-deposits.cancel');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

        Route::get('/support/messages', [SupportController::class, 'index'])->name('support.messages.index');
        Route::post('/support/messages', [SupportController::class, 'store'])->middleware('throttle:30,1')->name('support.messages.store');
        Route::post('/support/read', [SupportController::class, 'read'])->name('support.read');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});
