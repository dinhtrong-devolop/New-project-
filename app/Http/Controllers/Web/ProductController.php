<?php

namespace App\Http\Controllers\Web;

use App\Enums\InventoryStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->publiclyVisible()
            ->with(['category', 'images'])
            ->withCount([
                'inventories as stock_count' => fn (Builder $query) => $query
                    ->where('status', InventoryStatus::Available->value),
            ])
            ->when($request->string('search')->trim()->value(), function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery
                            ->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->string('category')->trim()->value(), fn (Builder $query, string $slug) => $query
                ->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('slug', $slug)))
            ->when($request->integer('min_price'), fn (Builder $query, int $price) => $query->where('price', '>=', $price))
            ->when($request->integer('max_price'), fn (Builder $query, int $price) => $query->where('price', '<=', $price))
            ->when($request->string('type')->trim()->value(), fn (Builder $query, string $type) => $query
                ->where('product_type', $type))
            ->when($request->integer('rating'), fn (Builder $query, int $rating) => $query
                ->where('rating_average', '>=', $rating))
            ->when($request->boolean('in_stock'), fn (Builder $query) => $query
                ->whereHas('inventories', fn (Builder $inventoryQuery) => $inventoryQuery
                    ->where('status', InventoryStatus::Available->value)))
            ->tap(fn (Builder $query) => $this->applySort($query, $request->string('sort')->value()))
            ->paginate(12)
            ->withQueryString();

        return view('storefront.products.index', [
            'products' => $products,
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'productTypes' => ProductType::cases(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->status->value === 'active' && $product->category()->where('is_active', true)->exists(), 404);

        $product->load([
            'category',
            'images',
            'reviews' => fn ($query) => $query
                ->where('status', ReviewStatus::Published->value)
                ->latest('published_at')
                ->with('user'),
        ])->loadCount([
            'inventories as stock_count' => fn (Builder $query) => $query
                ->where('status', InventoryStatus::Available->value),
        ]);

        $relatedProducts = Product::query()
            ->publiclyVisible()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->with(['category', 'images'])
            ->withCount([
                'inventories as stock_count' => fn (Builder $query) => $query
                    ->where('status', InventoryStatus::Available->value),
            ])
            ->orderByDesc('sold_count')
            ->limit(5)
            ->get();

        $reviewableOrderItem = auth()->check()
            ? OrderItem::query()
                ->where('product_id', $product->id)
                ->whereDoesntHave('review')
                ->whereHas('order', fn (Builder $query) => $query
                    ->where('user_id', auth()->id())
                    ->where('order_status', OrderStatus::Completed->value))
                ->latest()
                ->first()
            : null;

        return view('storefront.products.show', compact('product', 'relatedProducts', 'reviewableOrderItem'));
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'best_selling' => $query->orderByDesc('sold_count'),
            'latest' => $query->latest('published_at'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('rating_average'),
            default => $query->orderByDesc('is_featured')->orderByDesc('sold_count'),
        };
    }
}
