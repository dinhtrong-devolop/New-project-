@extends('layouts.admin')

@section('title', 'Danh mục')
@section('page_title', 'Quản lý danh mục')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">TAXONOMY</span><h1>Danh mục sản phẩm</h1><p>Sắp xếp cấu trúc duyệt sản phẩm trên storefront.</p></div></div>
    <div class="admin-split-layout">
        <section class="admin-panel">
            <div class="admin-panel__head"><div><span class="admin-eyebrow">DANH SÁCH</span><h2>{{ $categories->total() }} danh mục</h2></div></div>
            <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Thứ tự</th><th>Danh mục</th><th>Danh mục cha</th><th>Sản phẩm</th><th>Trạng thái</th><th></th></tr></thead><tbody>
                @foreach($categories as $category)<tr><td>{{ $category->sort_order }}</td><td><strong>{{ $category->name }}</strong><small class="admin-table-sub">/{{ $category->slug }}</small></td><td>{{ $category->parent?->name ?? '—' }}</td><td>{{ $category->products_count }}</td><td><span class="admin-badge {{ $category->is_active ? 'admin-badge--active' : 'admin-badge--inactive' }}">{{ $category->is_active ? 'Hoạt động' : 'Ngừng hoạt động' }}</span></td><td><div class="admin-row-actions"><a href="{{ route('admin.categories.edit', $category) }}">Sửa</a><form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Xóa danh mục này?')">@csrf @method('DELETE')<button>Xóa</button></form></div></td></tr>@endforeach
            </tbody></table></div><div class="admin-pagination">{{ $categories->links() }}</div>
        </section>
        <aside class="admin-panel admin-form-card">
            <div class="admin-form-card__head"><span class="admin-eyebrow">TẠO NHANH</span><h2>Danh mục mới</h2><p>Slug được tự động chuẩn hóa nếu để trống.</p></div>
            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="admin-form-grid admin-form-grid--one">@csrf
                <label class="admin-field"><span>Tên danh mục</span><input name="name" value="{{ old('name') }}" required></label>
                <label class="admin-field"><span>Slug</span><input name="slug" value="{{ old('slug') }}" placeholder="tu-dong-tu-ten"></label>
                <label class="admin-field"><span>Danh mục cha</span><select name="parent_id"><option value="">Không có</option>@foreach($parents as $parent)<option value="{{ $parent->id }}">{{ $parent->name }}</option>@endforeach</select></label>
                <label class="admin-field"><span>Mô tả</span><textarea name="description" rows="3">{{ old('description') }}</textarea></label>
                <label class="admin-field"><span>Ảnh danh mục</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                <label class="admin-field"><span>Thứ tự</span><input type="number" min="0" name="sort_order" value="{{ old('sort_order', 0) }}"></label>
                <label class="admin-toggle"><input type="checkbox" name="is_active" value="1" checked><span></span><b>Đang hoạt động</b></label>
                <button class="admin-button admin-button--primary admin-button--wide"><x-icon name="plus" :size="17" /> Tạo danh mục</button>
            </form>
        </aside>
    </div>
@endsection
