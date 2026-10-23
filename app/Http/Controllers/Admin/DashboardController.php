<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CardDepositStatus;
use App\Enums\InventoryStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CardDeposit;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();
        $paidOrders = Order::query()->where('payment_status', PaymentStatus::Paid->value);

        $revenueByDate = (clone $paidOrders)
            ->where('created_at', '>=', $today->copy()->subDays(6))
            ->selectRaw('DATE(created_at) as sale_date, SUM(total) as revenue')
            ->groupBy('sale_date')
            ->pluck('revenue', 'sale_date');

        $chart = collect(range(6, 0))->map(function (int $daysAgo) use ($revenueByDate): array {
            $date = today()->subDays($daysAgo);

            return [
                'label' => $date->format('d/m'),
                'value' => (int) ($revenueByDate[$date->toDateString()] ?? 0),
            ];
        });

        $lowStockProducts = Product::query()
            ->with('category')
            ->withCount(['inventories as available_stock' => fn (Builder $query) => $query->where('status', InventoryStatus::Available->value)])
            ->whereHas('inventories', fn (Builder $query) => $query->where('status', InventoryStatus::Available->value), '<=', 5)
            ->orderBy('available_stock')
            ->limit(6)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->where('role', UserRole::Customer->value)->count(),
                'products' => Product::query()->count(),
                'orders' => Order::query()->count(),
                'revenue' => (int) (clone $paidOrders)->sum('total'),
                'ordersToday' => Order::query()->whereDate('created_at', $today)->count(),
                'revenueToday' => (int) (clone $paidOrders)->whereDate('created_at', $today)->sum('total'),
                'pendingDeposits' => CardDeposit::query()->where('status', CardDepositStatus::Pending->value)->count(),
                'lowStock' => $lowStockProducts->count(),
            ],
            'chart' => $chart,
            'chartMaximum' => max(1, (int) $chart->max('value')),
            'topProducts' => Product::query()->with('category')->orderByDesc('sold_count')->limit(5)->get(),
            'recentOrders' => Order::query()->with('user')->latest()->limit(7)->get(),
            'lowStockProducts' => $lowStockProducts,
            'recentUsers' => User::query()->where('role', UserRole::Customer->value)->latest()->limit(5)->get(),
            'generatedAt' => Carbon::now(),
        ]);
    }
}
