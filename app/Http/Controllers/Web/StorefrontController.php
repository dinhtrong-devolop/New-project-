<?php

namespace App\Http\Controllers\Web;

use App\Enums\InventoryStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount([
                'products as active_products_count' => fn (Builder $query) => $query->active(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $catalog = Product::query()
            ->publiclyVisible()
            ->with(['category', 'images'])
            ->withCount([
                'inventories as stock_count' => fn (Builder $query) => $query
                    ->where('status', InventoryStatus::Available->value),
            ]);

        $featuredProducts = (clone $catalog)
            ->orderByDesc('is_featured')
            ->orderByDesc('sold_count')
            ->limit(10)
            ->get();

        $bestSellingProducts = (clone $catalog)
            ->orderByDesc('sold_count')
            ->limit(5)
            ->get();

        $newProducts = (clone $catalog)
            ->latest('published_at')
            ->limit(5)
            ->get();

        $promotionProducts = (clone $catalog)
            ->whereNotNull('original_price')
            ->whereColumn('original_price', '>', 'price')
            ->orderByDesc('original_price')
            ->limit(5)
            ->get();

        return view('storefront.home', [
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'bestSellingProducts' => $bestSellingProducts,
            'newProducts' => $newProducts,
            'promotionProducts' => $promotionProducts,
            'marketStats' => [
                'products' => Product::query()->publiclyVisible()->count(),
                'deliveries' => Product::query()->publiclyVisible()->sum('sold_count'),
                'inStock' => ProductInventory::query()
                    ->where('status', InventoryStatus::Available->value)
                    ->count(),
            ],
        ]);
    }
}
