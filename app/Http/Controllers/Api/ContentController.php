<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class ContentController extends ApiController
{
    public function page(string $slug): JsonResponse
    {
        $page = config("mobile.pages.{$slug}");
        abort_unless(is_array($page), 404);

        return $this->success(['slug' => $slug, ...$page], 'Page retrieved successfully');
    }

    public function faqs(): JsonResponse
    {
        return $this->success(config('mobile.faqs', []), 'FAQs retrieved successfully');
    }

    public function policies(): JsonResponse
    {
        $policies = collect(config('mobile.pages', []))
            ->map(fn (array $page, string $slug): array => ['slug' => $slug, ...$page])
            ->values()
            ->all();

        return $this->success($policies, 'Policies retrieved successfully');
    }
}
