<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Models\OrderItem;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, ReviewService $reviews): RedirectResponse
    {
        $item = OrderItem::query()->findOrFail($request->integer('order_item_id'));
        $reviews->create(
            $request->user(),
            $item,
            $request->integer('rating'),
            $request->string('comment')->trim()->value() ?: null,
        );

        return back()->with('status', 'Cảm ơn bạn đã đánh giá sản phẩm.');
    }
}
