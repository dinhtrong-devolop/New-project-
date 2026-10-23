<?php

namespace App\Http\Controllers\Api;

use App\Enums\InventoryStatus;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    public function suggestions(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = trim($data['q']);

        $products = Product::query()->publiclyVisible()
            ->where(fn (Builder $query) => $query->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"))
            ->orderByDesc('sold_count')->limit(8)->get(['id', 'name', 'slug', 'price']);
        $categories = Category::query()->where('is_active', true)->where('name', 'like', "%{$term}%")
            ->orderBy('sort_order')->limit(5)->get(['id', 'name', 'slug']);

        return $this->success(['products' => $products, 'categories' => $categories], 'Search suggestions retrieved successfully');
    }

    public function trending(Request $request): JsonResponse
    {
        $limit = min(max($request->integer('limit', 10), 1), 30);
        $products = $this->catalog()->orderByDesc('sold_count')->orderByDesc('rating_average')->limit($limit)->get();

        return $this->success(ProductResource::collection($products)->resolve(), 'Trending products retrieved successfully');
    }

    public function recentlyViewed(Request $request): JsonResponse
    {
        $views = $request->user()->productViews()
            ->whereHas('product', fn (Builder $query) => $query->publiclyVisible())
            ->with(['product.category', 'product.images'])
            ->latest('last_viewed_at')
            ->limit(min(max($request->integer('limit', 20), 1), 50))
            ->get();

        $products = $views->pluck('product')->each->loadCount([
            'inventories as stock_count' => fn (Builder $query) => $query->where('status', InventoryStatus::Available->value),
        ]);

        return $this->success(ProductResource::collection($products)->resolve(), 'Recently viewed products retrieved successfully');
    }

    private function catalog(): Builder
    {
        return Product::query()->publiclyVisible()->with(['category', 'images'])->withCount([
            'inventories as stock_count' => fn (Builder $query) => $query->where('status', InventoryStatus::Available->value),
        ]);
    }
}
