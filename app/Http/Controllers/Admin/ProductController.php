<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category', 'images'])
            ->withCount([
                'inventories as available_stock' => fn (Builder $query) => $query->where('status', InventoryStatus::Available->value),
                'inventories as total_stock',
            ])
            ->when($request->string('search')->trim()->value(), fn (Builder $query, string $search) => $query
                ->where(fn (Builder $inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($request->string('status')->value(), fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product,
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(ProductRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->safe()->except(['images', 'remove_images']);
        $data['published_at'] = $data['status'] === ProductStatus::Active->value ? now() : null;
        $product = Product::query()->create($data);
        $this->storeImages($request, $product);
        $logger->log($request, 'admin.product.created', $product, ['sku' => $product->sku]);

        return redirect()->route('admin.products.index')->with('status', 'Đã tạo sản phẩm mới.');
    }

    public function edit(Product $product): View
    {
        $product->load('images');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->safe()->except(['images', 'remove_images']);

        if ($data['status'] === ProductStatus::Active->value && ! $product->published_at) {
            $data['published_at'] = now();
        }

        $product->update($data);

        $imagesToRemove = ProductImage::query()
            ->where('product_id', $product->id)
            ->whereIn('id', $request->input('remove_images', []))
            ->get();

        foreach ($imagesToRemove as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        $this->storeImages($request, $product);
        $logger->log($request, 'admin.product.updated', $product, ['sku' => $product->sku]);

        return redirect()->route('admin.products.index')->with('status', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Request $request, Product $product, ActivityLogger $logger): RedirectResponse
    {
        if ($product->inventories()->exists() || $product->orderItems()->exists()) {
            return back()->withErrors([
                'product' => 'Không thể xóa sản phẩm đã có dữ liệu kho hoặc đơn hàng vì cần bảo toàn lịch sử giao dịch.',
            ]);
        }

        $imagePaths = $product->images()->pluck('path')->all();

        $logger->log($request, 'admin.product.deleted', $product, ['sku' => $product->sku]);
        $product->forceDelete();
        Storage::disk('public')->delete($imagePaths);

        return back()->with('status', 'Đã xóa sản phẩm.');
    }

    private function storeImages(ProductRequest $request, Product $product): void
    {
        $nextSort = (int) $product->images()->max('sort_order');
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($request->file('images', []) as $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'alt_text' => $product->name,
                'sort_order' => ++$nextSort,
                'is_primary' => ! $hasPrimary,
            ]);
            $hasPrimary = true;
        }

        if (! $product->images()->where('is_primary', true)->exists()) {
            $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }
    }
}
