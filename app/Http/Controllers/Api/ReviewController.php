<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ReviewUpdateRequest;
use App\Http\Requests\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends ApiController
{
    public function index(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->newQuery()->whereKey($product->id)->publiclyVisible()->exists(), 404);
        $reviews = $product->reviews()->where('status', 'published')->with('user')->latest('published_at')
            ->paginate(min(max($request->integer('per_page', 20), 1), 50));

        return $this->success(
            ReviewResource::collection($reviews->getCollection())->resolve(),
            'Reviews retrieved successfully',
            200,
            ['current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(), 'total' => $reviews->total()],
        );
    }

    public function mine(Request $request): JsonResponse
    {
        $reviews = $request->user()->reviews()->with(['user', 'product:id,name,slug'])->latest()->paginate(min(max($request->integer('per_page', 20), 1), 50));

        return $this->success(
            ReviewResource::collection($reviews->getCollection())->resolve(),
            'Your reviews retrieved successfully',
            200,
            ['current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(), 'total' => $reviews->total()],
        );
    }

    public function store(ReviewRequest $request, ReviewService $reviews): JsonResponse
    {
        $item = OrderItem::query()->findOrFail($request->integer('order_item_id'));
        $review = $reviews->create(
            $request->user(),
            $item,
            $request->integer('rating'),
            $request->string('comment')->trim()->value() ?: null,
        );

        return $this->success((new ReviewResource($review))->resolve(), 'Review created successfully', 201);
    }

    public function update(ReviewUpdateRequest $request, Review $review, ReviewService $reviews): JsonResponse
    {
        abort_unless((int) $review->user_id === (int) $request->user()->id, 404);
        $review = $reviews->update($review, $request->integer('rating'), $request->string('comment')->trim()->value() ?: null);

        return $this->success((new ReviewResource($review))->resolve(), 'Review updated successfully');
    }

    public function destroy(Request $request, Review $review, ReviewService $reviews): JsonResponse
    {
        abort_unless((int) $review->user_id === (int) $request->user()->id, 404);
        $reviews->delete($review);

        return $this->success(null, 'Review deleted successfully');
    }
}
