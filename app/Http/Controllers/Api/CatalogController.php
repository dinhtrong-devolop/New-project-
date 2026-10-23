<?php

namespace App\Http\Controllers\Api;

use App\Enums\InventoryStatus;
use App\Enums\ReviewStatus;
use App\Http\Requests\Api\ProductIndexRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends ApiController
{
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn (Builder $query) => $query->active()])
            ->orderBy('sort_order')
            ->get();

        return $this->success(CategoryResource::collection($categories)->resolve(), 'Categories retrieved successfully');
    }

    public function products(ProductIndexRequest $request): JsonResponse
    {
        $products = Product::query()
            ->publiclyVisible()
            ->with(['category', 'images'])
            ->withCount(['inventories as stock_count' => fn (Builder $query) => $query->where('status', InventoryStatus::Available->value)])
            ->when($request->string('search')->trim()->value(), function (Builder $query, string $search): void {
                $query->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$search}%")));
            })
            ->when($request->string('category')->trim()->value(), fn (Builder $query, string $slug) => $query
                ->whereHas('category', fn (Builder $category) => $category->where('slug', $slug)))
            ->when($request->integer('min_price'), fn (Builder $query, int $price) => $query->where('price', '>=', $price))
            ->when($request->integer('max_price'), fn (Builder $query, int $price) => $query->where('price', '<=', $price))
            ->when($request->string('type')->trim()->value(), fn (Builder $query, string $type) => $query->where('product_type', $type))
            ->when($request->integer('rating'), fn (Builder $query, int $rating) => $query->where('rating_average', '>=', $rating))
            ->when($request->boolean('in_stock'), fn (Builder $query) => $query->whereHas('inventories', fn (Builder $inventory) => $inventory->where('status', InventoryStatus::Available->value)))
            ->tap(fn (Builder $query) => $this->sort($query, $request->string('sort')->value()))
            ->paginate(min(max($request->integer('per_page', 12), 1), 50))
            ->withQueryString();

        return $this->success(
            ProductResource::collection($products->getCollection())->resolve(),
            'Products retrieved successfully',
            200,
            [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        );
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->status->value === 'active' && $product->category()->where('is_active', true)->exists(), 404);
        $product->load([
            'category',
            'images',
            'reviews' => fn ($query) => $query->where('status', ReviewStatus::Published->value)->latest('published_at')->with('user'),
        ])->loadCount([
            'inventories as stock_count' => fn (Builder $query) => $query->where('status', InventoryStatus::Available->value),
        ]);

        if ($user = $request->user('sanctum')) {
            ProductView::query()->updateOrCreate(
                ['user_id' => $user->id, 'product_id' => $product->id],
                ['last_viewed_at' => now()],
            );
        }

        return $this->success((new ProductResource($product))->resolve(), 'Product retrieved successfully');
    }

    private function sort(Builder $query, string $sort): void
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
