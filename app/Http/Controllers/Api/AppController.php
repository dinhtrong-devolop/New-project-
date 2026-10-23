<?php

namespace App\Http\Controllers\Api;

use App\Enums\InventoryStatus;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class AppController extends ApiController
{
    public function config(): JsonResponse
    {
        $settings = SiteSetting::current();

        return $this->success([
            'site' => [
                'name' => $settings->site_name,
                'description' => $settings->site_description,
                'contact_email' => $settings->contact_email,
                'chat_label' => $settings->chat_label,
                'chat_url' => $settings->chat_url,
            ],
            'branding' => [
                'logo_url' => $settings->assetUrl('logo_path'),
                'dark_logo_url' => $settings->assetUrl('dark_logo_path'),
                'favicon_url' => $settings->assetUrl('favicon_path'),
                'banner_url' => $settings->assetUrl('banner_path'),
                'cover_url' => $settings->assetUrl('cover_path'),
                'primary_color' => '#ef3833',
                'dark_background' => '#0d0d0e',
            ],
            'app' => [
                'api_version' => 'v1',
                'min_supported_version' => config('mobile.min_supported_version'),
                'latest_version' => config('mobile.latest_version'),
                'force_update' => config('mobile.force_update'),
                'maintenance' => app()->isDownForMaintenance(),
                'maintenance_message' => config('mobile.maintenance_message'),
            ],
            'footer' => [
                'description' => $settings->footerText('footer_description'),
                'trust_text' => $settings->footerText('footer_trust_text'),
                'columns' => $settings->resolvedFooterColumns(),
                'copyright' => $settings->footerText('footer_copyright'),
                'bottom_text' => $settings->footerText('footer_bottom_text'),
            ],
        ], 'App configuration retrieved successfully');
    }

    public function home(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn (Builder $query) => $query->active()])
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        $catalog = Product::query()
            ->publiclyVisible()
            ->with(['category', 'images'])
            ->withCount(['inventories as stock_count' => fn (Builder $query) => $query->where('status', InventoryStatus::Available->value)]);

        return $this->success([
            'categories' => CategoryResource::collection($categories)->resolve(),
            'featured_products' => ProductResource::collection((clone $catalog)->orderByDesc('is_featured')->orderByDesc('sold_count')->limit(10)->get())->resolve(),
            'best_selling_products' => ProductResource::collection((clone $catalog)->orderByDesc('sold_count')->limit(10)->get())->resolve(),
            'new_products' => ProductResource::collection((clone $catalog)->latest('published_at')->limit(10)->get())->resolve(),
            'promotion_products' => ProductResource::collection((clone $catalog)->whereNotNull('original_price')->whereColumn('original_price', '>', 'price')->limit(10)->get())->resolve(),
            'stats' => [
                'products' => Product::query()->publiclyVisible()->count(),
                'deliveries' => Product::query()->publiclyVisible()->sum('sold_count'),
                'in_stock' => ProductInventory::query()->where('status', InventoryStatus::Available->value)->count(),
            ],
        ], 'Home data retrieved successfully');
    }
}
