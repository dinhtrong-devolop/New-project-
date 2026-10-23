@extends('layouts.admin')

@section('title', 'Kho sản phẩm số')
@section('page_title', 'Kho sản phẩm số')

@section('content')
    <div class="admin-page-head">
        <div>
            <span class="admin-eyebrow">SECURE INVENTORY</span>
            <h1>Kho key & tài khoản</h1>
            <p>Dữ liệu nhạy cảm được mã hóa; giao diện chỉ hiển thị gợi ý đã che.</p>
        </div>
    </div>

    <nav class="admin-inventory-stats" aria-label="Phân loại kho theo trạng thái">
        @foreach(App\Enums\InventoryStatus::cases() as $status)
            @php
                $statusUrl = route('admin.inventory.index', array_filter([
                    'product_id' => request('product_id'),
                    'type' => request('type'),
                    'status' => $selectedStatus === $status ? null : $status->value,
                ]));
            @endphp
            <a href="{{ $statusUrl }}" @class(['is-active' => $selectedStatus === $status]) @if($selectedStatus === $status) aria-current="page" @endif>
                <span class="admin-badge admin-badge--{{ $status->value }}">{{ $status->label() }}</span>
                <strong>{{ number_format($statusCounts[$status->value] ?? 0) }}</strong>
                <small>{{ match($status) {
                    App\Enums\InventoryStatus::Available => 'Sẵn sàng giao',
                    App\Enums\InventoryStatus::Reserved => 'Đang giữ chỗ',
                    App\Enums\InventoryStatus::Sold => 'Đã bàn giao',
                    App\Enums\InventoryStatus::Disabled => 'Không còn sử dụng',
                } }}</small>
            </a>
        @endforeach
    </nav>

    <div class="admin-split-layout admin-split-layout--inventory">
        <section class="admin-panel admin-inventory-list">
            <div class="admin-inventory-list__head">
                <div>
                    <span class="admin-eyebrow">DANH SÁCH KHO</span>
                    <h2>{{ $selectedStatus?->label() ?? 'Tất cả trạng thái' }}</h2>
                </div>
                <span>{{ number_format($inventory->total()) }} mục</span>
            </div>

            <form method="GET" action="{{ route('admin.inventory.index') }}" class="admin-filterbar admin-inventory-filters">
                <label>
                    <span>Sản phẩm</span>
                    <select name="product_id">
                        <option value="">Tất cả sản phẩm</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Loại dữ liệu</span>
                    <select name="type">
                        <option value="">Tất cả loại</option>
                        @foreach(App\Enums\ProductType::cases() as $type)
                            <option value="{{ $type->value }}" @selected($selectedType === $type)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Trạng thái</span>
                    <select name="status">
                        <option value="">Tất cả trạng thái</option>
                        @foreach(App\Enums\InventoryStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($selectedStatus === $status)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="admin-inventory-filters__actions">
                    <button type="submit" class="admin-button">Lọc kho</button>
                    @if(request()->hasAny(['product_id', 'type', 'status']))
                        <a href="{{ route('admin.inventory.index') }}">Xóa bộ lọc</a>
                    @endif
                </div>
            </form>

            @error('inventory_ids')
                <div class="admin-inventory-error" role="alert">{{ $message }}</div>
            @enderror

            <div class="admin-inventory-bulkbar" data-inventory-bulkbar>
                <label>
                    <input type="checkbox" data-inventory-select-all>
                    <span>Chọn tất cả mục có thể xóa trên trang này</span>
                </label>
                <form id="inventory-bulk-delete-form" action="{{ route('admin.inventory.bulk-destroy') }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn tất cả mục kho đã chọn? Dữ liệu mã hóa sẽ không thể khôi phục.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-button admin-button--danger" data-inventory-bulk-delete disabled>
                        <x-icon name="trash" :size="15" /> Xóa mục đã chọn (<span data-inventory-selected-count>0</span>)
                    </button>
                </form>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table admin-inventory-table">
                    <thead>
                        <tr>
                            <th class="admin-inventory-check-column"><span class="sr-only">Chọn</span></th>
                            <th>ID</th>
                            <th>Sản phẩm</th>
                            <th>Gợi ý dữ liệu</th>
                            <th>Loại</th>
                            <th>Trạng thái</th>
                            <th>Ngày nhập</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventory as $item)
                            <tr class="admin-inventory-row admin-inventory-row--{{ $item->status->value }}">
                                <td class="admin-inventory-check-column" data-label="Chọn">
                                    @if(in_array($item->status, [App\Enums\InventoryStatus::Available, App\Enums\InventoryStatus::Disabled], true))
                                        <input type="checkbox" name="inventory_ids[]" value="{{ $item->id }}" form="inventory-bulk-delete-form" aria-label="Chọn mục kho #{{ $item->id }}" data-inventory-checkbox>
                                    @else
                                        <input type="checkbox" disabled aria-label="Không thể chọn mục kho #{{ $item->id }}">
                                    @endif
                                </td>
                                <td data-label="ID"><strong>#{{ $item->id }}</strong></td>
                                <td data-label="Sản phẩm">
                                    <strong>{{ $item->product->name }}</strong>
                                    <small class="admin-table-sub">{{ $item->product->sku }}</small>
                                </td>
                                <td data-label="Gợi ý dữ liệu"><code>{{ $item->display_hint }}</code></td>
                                <td data-label="Loại"><span class="admin-inventory-type">{{ $item->inventory_type->label() }}</span></td>
                                <td data-label="Trạng thái"><span class="admin-badge admin-badge--{{ $item->status->value }}">{{ $item->status->label() }}</span></td>
                                <td data-label="Ngày nhập">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                <td data-label="Thao tác">
                                    <div class="admin-row-actions">
                                        @if(!in_array($item->status, [App\Enums\InventoryStatus::Sold, App\Enums\InventoryStatus::Disabled], true))
                                            <form action="{{ route('admin.inventory.disable', $item) }}" method="POST" onsubmit="return confirm('Vô hiệu hóa mục kho này?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit">Vô hiệu</button>
                                            </form>
                                        @endif

                                        @if(in_array($item->status, [App\Enums\InventoryStatus::Available, App\Enums\InventoryStatus::Disabled], true))
                                            <form action="{{ route('admin.inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn mục kho #{{ $item->id }}? Dữ liệu mã hóa sẽ không thể khôi phục.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="admin-inventory-delete" aria-label="Xóa mục kho #{{ $item->id }}">
                                                    <x-icon name="trash" :size="14" /> Xóa
                                                </button>
                                            </form>
                                        @endif

                                        @if($item->status === App\Enums\InventoryStatus::Sold)
                                            <span class="admin-table-sub">Đã khóa</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="admin-empty">Kho chưa có dữ liệu phù hợp với bộ lọc.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="admin-pagination">{{ $inventory->links() }}</div>
        </section>

        <aside class="admin-panel admin-form-card">
            <div class="admin-form-card__head">
                <span class="admin-eyebrow">NHẬP HÀNG LOẠT</span>
                <h2>Import kho số</h2>
                <p>Mỗi dòng là một key hoặc một bộ thông tin tài khoản. Dữ liệu được mã hóa trước khi lưu.</p>
            </div>
            <form action="{{ route('admin.inventory.store') }}" method="POST" class="admin-form-grid admin-form-grid--one">
                @csrf
                <label class="admin-field">
                    <span>Sản phẩm</span>
                    <select name="product_id" required>
                        <option value="">Chọn sản phẩm</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="admin-field">
                    <span>Dữ liệu tồn kho</span>
                    <textarea name="inventory" rows="13" placeholder="KEY-001&#10;KEY-002&#10;email@example.com|username|password|thông tin thêm" required></textarea>
                    <small>Mỗi dòng là một key. Với loại account dùng email|username|password|thông tin thêm hoặc JSON. Mục trùng được bỏ qua bằng fingerprint.</small>
                </label>
                <button class="admin-button admin-button--primary admin-button--wide"><x-icon name="key" :size="18" /> Mã hóa & nhập kho</button>
            </form>
        </aside>
    </div>
@endsection
