<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()->with('parent')->withCount(['products', 'children'])->orderBy('sort_order')->paginate(20),
            'parents' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(CategoryRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::query()->create($data);
        $logger->log($request, 'admin.category.created', $category);

        return back()->with('status', 'Đã tạo danh mục mới.');
    }

    public function edit(Category $category): View
    {
        $excludedParentIds = [$category->id, ...$category->descendantIds()];

        return view('admin.categories.edit', [
            'category' => $category,
            'parents' => Category::query()->whereNotIn('id', $excludedParentIds)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if (($request->boolean('remove_image') || $request->hasFile('image')) && $category->image_path) {
            Storage::disk('public')->delete($category->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);
        $logger->log($request, 'admin.category.updated', $category);

        return redirect()->route('admin.categories.index')->with('status', 'Đã cập nhật danh mục.');
    }

    public function destroy(Request $request, Category $category, ActivityLogger $logger): RedirectResponse
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->withErrors(['category' => 'Không thể xóa danh mục đang có sản phẩm hoặc danh mục con.']);
        }

        $logger->log($request, 'admin.category.deleted', $category, ['name' => $category->name]);
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }
        $category->delete();

        return back()->with('status', 'Đã xóa danh mục.');
    }
}
