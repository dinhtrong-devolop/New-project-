<?php

use App\Http\Controllers\Account\PasswordController as AccountPasswordController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Admin\ApiDocumentationController as AdminApiDocumentationController;
use App\Http\Controllers\Admin\BankAccountController as AdminBankAccountController;
use App\Http\Controllers\Admin\BankDepositController as AdminBankDepositController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CronJobController as AdminCronJobController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Admin\DepositSettingController as AdminDepositSettingController;
use App\Http\Controllers\Admin\HistoryController as AdminHistoryController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SiteSettingController as AdminSiteSettingController;
use App\Http\Controllers\Admin\SupportConversationController as AdminSupportConversationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Web\AccountPageController;
use App\Http\Controllers\Web\BankDepositController;
use App\Http\Controllers\Web\CartPageController;
use App\Http\Controllers\Web\CheckoutPageController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ReviewController;
use App\Http\Controllers\Web\StorefrontController;
use App\Http\Controllers\Web\SupportChatController;
use App\Http\Controllers\Web\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/cart', [CartPageController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartPageController::class, 'store'])->name('cart.store');
Route::patch('/cart/{item}', [CartPageController::class, 'update'])->name('cart.update');
Route::delete('/cart/{item}', [CartPageController::class, 'destroy'])->name('cart.destroy');
Route::post('/cart/voucher', [CartPageController::class, 'applyVoucher'])->name('cart.voucher.store');
Route::delete('/cart/voucher', [CartPageController::class, 'removeVoucher'])->name('cart.voucher.destroy');
Route::get('/checkout', [CheckoutPageController::class, 'index'])->name('checkout.index');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/account', AccountPageController::class)->name('account.index');
    Route::patch('/account/profile', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::put('/account/password', [AccountPasswordController::class, 'update'])->name('account.password.update');
    Route::post('/checkout', [CheckoutPageController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/deposits', [WalletController::class, 'deposits'])->name('wallet.deposits');
    Route::get('/wallet/deposit/card', [WalletController::class, 'createDeposit'])->name('wallet.deposit.card');
    Route::post('/wallet/deposit/card', [WalletController::class, 'storeDeposit'])->name('wallet.deposit.card.store');
    Route::get('/wallet/deposit/bank', [BankDepositController::class, 'index'])->name('wallet.deposit.bank');
    Route::post('/wallet/deposit/bank', [BankDepositController::class, 'store'])->name('wallet.deposit.bank.store');
    Route::get('/wallet/deposit/bank/{bankDeposit}', [BankDepositController::class, 'show'])->name('wallet.deposit.bank.show');
    Route::get('/wallet/deposit/bank/{bankDeposit}/status', [BankDepositController::class, 'status'])->name('wallet.deposit.bank.status');
    Route::get('/support/chat/messages', [SupportChatController::class, 'index'])->name('support.chat.messages');
    Route::post('/support/chat/messages', [SupportChatController::class, 'store'])->middleware('throttle:30,1')->name('support.chat.messages.store');
    Route::post('/support/chat/read', [SupportChatController::class, 'read'])->name('support.chat.read');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'admin'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/api-docs', AdminApiDocumentationController::class)->name('api-docs');

    Route::resource('products', AdminProductController::class)->except('show');
    Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [AdminInventoryController::class, 'store'])->name('inventory.store');
    Route::delete('/inventory/bulk', [AdminInventoryController::class, 'bulkDestroy'])->name('inventory.bulk-destroy');
    Route::patch('/inventory/{inventory}/disable', [AdminInventoryController::class, 'disable'])->name('inventory.disable');
    Route::delete('/inventory/{inventory}', [AdminInventoryController::class, 'destroy'])->name('inventory.destroy');

    Route::resource('categories', AdminCategoryController::class)->except(['create', 'show']);
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    Route::patch('/orders/{order}/refund-request', [AdminOrderController::class, 'refundRequest'])->name('orders.refund-request');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/lock', [AdminUserController::class, 'toggleLock'])->name('users.lock');
    Route::post('/users/{user}/wallet-adjustment', [AdminUserController::class, 'adjustWallet'])->name('users.wallet-adjustment');

    Route::get('/deposits', [AdminDepositController::class, 'index'])->name('deposits.index');
    Route::get('/deposits/settings', [AdminDepositSettingController::class, 'edit'])->name('deposits.settings.edit');
    Route::put('/deposits/settings', [AdminDepositSettingController::class, 'update'])->name('deposits.settings.update');
    Route::resource('bank-accounts', AdminBankAccountController::class)->except(['show', 'destroy']);
    Route::patch('/bank-accounts/{bankAccount}/toggle', [AdminBankAccountController::class, 'toggle'])->name('bank-accounts.toggle');
    Route::get('/bank-deposits', [AdminBankDepositController::class, 'index'])->name('bank-deposits.index');
    Route::post('/bank-deposits/{bankDeposit}/approve', [AdminBankDepositController::class, 'approve'])->name('bank-deposits.approve');
    Route::get('/history/transactions', [AdminHistoryController::class, 'transactions'])->name('history.transactions');
    Route::get('/history/orders', [AdminHistoryController::class, 'orders'])->name('history.orders');
    Route::get('/history/card-deposits', [AdminHistoryController::class, 'cardDeposits'])->name('history.card-deposits');
    Route::get('/history/voucher-usages', [AdminHistoryController::class, 'voucherUsages'])->name('history.voucher-usages');
    Route::get('/settings/general', [AdminSiteSettingController::class, 'edit'])->name('settings.general.edit');
    Route::put('/settings/general', [AdminSiteSettingController::class, 'update'])->name('settings.general.update');
    Route::get('/cron-jobs', [AdminCronJobController::class, 'index'])->name('cron-jobs.index');
    Route::patch('/cron-jobs/{cronJob}/toggle', [AdminCronJobController::class, 'toggle'])->name('cron-jobs.toggle');
    Route::post('/cron-jobs/{cronJob}/run', [AdminCronJobController::class, 'run'])->middleware('throttle:6,1')->name('cron-jobs.run');
    Route::get('/support', [AdminSupportConversationController::class, 'index'])->name('support.index');
    Route::get('/support/{supportConversation}/messages', [AdminSupportConversationController::class, 'messages'])->name('support.messages');
    Route::post('/support/{supportConversation}/messages', [AdminSupportConversationController::class, 'reply'])->middleware('throttle:60,1')->name('support.reply');
    Route::patch('/support/{supportConversation}/status', [AdminSupportConversationController::class, 'updateStatus'])->name('support.status');
    Route::get('/vouchers', [AdminVoucherController::class, 'index'])->name('vouchers.index');
    Route::post('/vouchers', [AdminVoucherController::class, 'store'])->name('vouchers.store');
    Route::patch('/vouchers/{voucher}/toggle', [AdminVoucherController::class, 'toggle'])->name('vouchers.toggle');
});
