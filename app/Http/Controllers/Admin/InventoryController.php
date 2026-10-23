<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryStatus;
use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InventoryImportRequest;
use App\Models\OrderDelivery;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Services\ActivityLogger;
use App\Services\DigitalInventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $selectedStatus = InventoryStatus::tryFrom($request->string('status')->value());
        $selectedType = ProductType::tryFrom($request->string('type')->value());
        $selectedProductId = $request->integer('product_id');

        $inventory = ProductInventory::query()
            ->with('product')
            ->when($selectedProductId, fn (Builder $query) => $query->where('product_id', $selectedProductId))
            ->when($selectedType, fn (Builder $query) => $query->where('inventory_type', $selectedType->value))
            ->when($selectedStatus, fn (Builder $query) => $query->where('status', $selectedStatus->value))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $statusCounts = ProductInventory::query()
            ->when($selectedProductId, fn (Builder $query) => $query->where('product_id', $selectedProductId))
            ->when($selectedType, fn (Builder $query) => $query->where('inventory_type', $selectedType->value))
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.inventory.index', [
            'inventory' => $inventory,
            'products' => Product::query()->orderBy('name')->get(),
            'statusCounts' => $statusCounts,
            'selectedStatus' => $selectedStatus,
            'selectedType' => $selectedType,
        ]);
    }

    public function store(InventoryImportRequest $request, DigitalInventoryService $service, ActivityLogger $logger): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $result = $service->import($product, $request->string('inventory')->value());
        $logger->log($request, 'admin.inventory.imported', $product, $result);

        return back()->with('status', "Đã nhập {$result['created']} mục kho; bỏ qua {$result['duplicates']} mục trùng.");
    }

    public function disable(Request $request, ProductInventory $inventory, ActivityLogger $logger): RedirectResponse
    {
        abort_if($inventory->status === InventoryStatus::Sold, 422, 'Không thể vô hiệu hóa dữ liệu đã bán.');

        $inventory->update([
            'status' => InventoryStatus::Disabled,
            'disabled_at' => now(),
            'reservation_token' => null,
            'reserved_at' => null,
            'reserved_until' => null,
        ]);
        $logger->log($request, 'admin.inventory.disabled', $inventory);

        return back()->with('status', 'Đã vô hiệu hóa mục kho.');
    }

    public function destroy(Request $request, ProductInventory $inventory, ActivityLogger $logger): RedirectResponse
    {
        DB::transaction(function () use ($request, $inventory, $logger): void {
            $lockedInventory = ProductInventory::query()
                ->with('product:id,name,sku')
                ->lockForUpdate()
                ->findOrFail($inventory->getKey());

            abort_if(
                in_array($lockedInventory->status, [InventoryStatus::Reserved, InventoryStatus::Sold], true)
                || $lockedInventory->delivery()->exists(),
                422,
                'Chỉ có thể xóa mục kho có sẵn hoặc đã vô hiệu hóa và chưa phát sinh giao dịch.'
            );

            $logger->log($request, 'admin.inventory.deleted', $lockedInventory, [
                'product_id' => $lockedInventory->product_id,
                'product_sku' => $lockedInventory->product->sku,
                'display_hint' => $lockedInventory->display_hint,
                'status' => $lockedInventory->status->value,
            ]);

            $lockedInventory->delete();
        });

        return back()->with('status', 'Đã xóa mục kho an toàn.');
    }

    public function bulkDestroy(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'inventory_ids' => ['required', 'array', 'min:1', 'max:100'],
            'inventory_ids.*' => ['required', 'integer', 'distinct', 'exists:product_inventories,id'],
        ], [
            'inventory_ids.required' => 'Vui lòng chọn ít nhất một mục kho để xóa.',
            'inventory_ids.max' => 'Mỗi lần chỉ có thể xóa tối đa 100 mục kho.',
        ]);

        $inventoryIds = collect($validated['inventory_ids'])->map(fn (mixed $id): int => (int) $id)->values();

        DB::transaction(function () use ($request, $logger, $inventoryIds): void {
            $items = ProductInventory::query()
                ->whereKey($inventoryIds)
                ->lockForUpdate()
                ->get();

            $hasProtectedItem = $items->count() !== $inventoryIds->count()
                || $items->contains(fn (ProductInventory $item): bool => in_array($item->status, [InventoryStatus::Reserved, InventoryStatus::Sold], true))
                || OrderDelivery::query()->whereIn('product_inventory_id', $inventoryIds)->exists();

            if ($hasProtectedItem) {
                throw ValidationException::withMessages([
                    'inventory_ids' => 'Danh sách có mục đang giữ chỗ, đã bán hoặc đã phát sinh giao dịch. Không có mục nào bị xóa.',
                ]);
            }

            $logger->log($request, 'admin.inventory.bulk_deleted', metadata: [
                'count' => $items->count(),
                'inventory_ids' => $items->pluck('id')->all(),
            ]);

            ProductInventory::query()->whereKey($inventoryIds)->delete();
        });

        return back()->with('status', "Đã xóa an toàn {$inventoryIds->count()} mục kho.");
    }
}
