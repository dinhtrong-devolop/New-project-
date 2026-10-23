<?php

namespace App\Http\Controllers\Api;

use App\Enums\InventoryStatus;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->wishlists()
            ->whereHas('product', fn (Builder $query) => $query->publiclyVisible())
            ->with(['product.category', 'product.images'])
            ->latest()
            ->paginate(min(max($request->integer('per_page', 20), 1), 50));

        $products = $items->getCollection()->pluck('product')->each->loadCount([
            'inventories as stock_count' => fn (Builder $query) => $query->where('status', InventoryStatus::Available->value),
        ]);

        return $this->success(
            ProductResource::collection($products)->resolve(),
            'Wishlist retrieved successfully',
            200,
            ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()],
        );
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->newQuery()->whereKey($product->id)->publiclyVisible()->exists(), 404);
        $wishlist = Wishlist::query()->firstOrCreate(['user_id' => $request->user()->id, 'product_id' => $product->id]);

        return $this->success(['product_id' => $product->id], 'Product added to wishlist', $wishlist->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $request->user()->wishlists()->where('product_id', $product->id)->delete();

        return $this->success(null, 'Product removed from wishlist');
    }
}
