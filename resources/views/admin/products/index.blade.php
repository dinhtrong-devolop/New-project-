@extends('layouts.admin')

@section('title', 'Sản phẩm')
@section('page_title', 'Quản lý sản phẩm')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">CATALOG</span><h1>Sản phẩm</h1><p>Quản lý giá, trạng thái, danh mục và tồn kho sản phẩm số.</p></div><a href="{{ route('admin.products.create') }}" class="admin-button admin-button--primary"><x-icon name="plus" :size="18" /> Thêm sản phẩm</a></div>
    <section class="admin-panel">
        <form method="GET" class="admin-filterbar"><div class="admin-search"><x-icon name="search" :size="17" /><input name="search" value="{{ request('search') }}" placeholder="Tìm tên hoặc SKU"></div><select name="status"><option value="">Tất cả trạng thái</option>@foreach (App\Enums\ProductStatus::manageableCases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select><button class="admin-button">Lọc dữ liệu</button></form>
        <div class="admin-table-wrap"><table class="admin-table admin-table--products"><thead><tr><th>Sản phẩm</th><th>Danh mục</th><th>Giá</th><th>Kho</th><th>Đã bán</th><th>Trạng thái</th><th></th></tr></thead><tbody>
            @forelse ($products as $product)
                <tr><td><div class="admin-product-cell"><x-product-art :product="$product" /><div><strong>{{ $product->name }}</strong><small>{{ $product->sku }} · {{ $product->product_type->label() }}</small></div></div></td><td>{{ $product->category->name }}</td><td><strong>{{ number_format($product->price, 0, ',', '.') }}₫</strong></td><td><span @class(['admin-stock', 'is-low' => $product->available_stock <= 5])>{{ $product->available_stock }}/{{ $product->total_stock }}</span></td><td>{{ number_format($product->sold_count) }}</td><td><span class="admin-badge admin-badge--{{ $product->status->value }}">{{ $product->status->label() }}</span></td><td><div class="admin-row-actions"><a href="{{ route('admin.products.edit', $product) }}">Sửa</a><form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn sản phẩm này?')">@csrf @method('DELETE')<button type="submit">Xóa</button></form></div></td></tr>
            @empty<tr><td colspan="7"><div class="admin-empty">Không tìm thấy sản phẩm.</div></td></tr>@endforelse
        </tbody></table></div>
        <div class="admin-pagination">{{ $products->links() }}</div>
    </section>
@endsection
