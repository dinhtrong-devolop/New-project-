<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function create(User $user, OrderItem $item, int $rating, ?string $comment): Review
    {
        $item->loadMissing('order');

        if ($item->order->user_id !== $user->id || $item->order->order_status !== OrderStatus::Completed) {
            throw ValidationException::withMessages(['review' => 'Bạn chỉ có thể đánh giá sản phẩm trong đơn đã hoàn tất.']);
        }

        if (Review::query()->where('order_item_id', $item->id)
            ->orWhere(fn ($query) => $query->where('user_id', $user->id)->where('product_id', $item->product_id))
            ->exists()) {
            throw ValidationException::withMessages(['review' => 'Bạn đã đánh giá sản phẩm này.']);
        }

        return DB::transaction(function () use ($user, $item, $rating, $comment): Review {
            $review = Review::query()->create([
                'user_id' => $user->id,
                'product_id' => $item->product_id,
                'order_item_id' => $item->id,
                'rating' => $rating,
                'comment' => $comment,
                'status' => ReviewStatus::Published,
                'published_at' => now(),
            ]);

            $this->syncProductStats($item->product_id);

            return $review->load('user');
        });
    }

    public function update(Review $review, int $rating, ?string $comment): Review
    {
        return DB::transaction(function () use ($review, $rating, $comment): Review {
            $review->update(['rating' => $rating, 'comment' => $comment]);
            $this->syncProductStats($review->product_id);

            return $review->fresh()->load('user');
        });
    }

    public function delete(Review $review): void
    {
        DB::transaction(function () use ($review): void {
            $productId = $review->product_id;
            $review->delete();
            $this->syncProductStats($productId);
        });
    }

    private function syncProductStats(int $productId): void
    {
        $stats = Review::query()
            ->where('product_id', $productId)
            ->where('status', ReviewStatus::Published->value)
            ->selectRaw('COUNT(*) as aggregate_count, AVG(rating) as aggregate_average')
            ->first();

        Product::query()->whereKey($productId)->update([
            'rating_count' => (int) ($stats->aggregate_count ?? 0),
            'rating_average' => round((float) ($stats->aggregate_average ?? 0), 2),
        ]);
    }
}
