@extends('layouts.admin')

@section('title', 'Sửa danh mục')
@section('page_title', 'Chỉnh sửa danh mục')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">TAXONOMY</span><h1>{{ $category->name }}</h1><p>Cập nhật cấu trúc và thông tin danh mục.</p></div><a href="{{ route('admin.categories.index') }}" class="admin-button">Quay lại</a></div>
    <section class="admin-panel admin-form-card admin-form-card--narrow"><form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="admin-form-grid">@csrf @method('PUT')
        <label class="admin-field"><span>Tên danh mục</span><input name="name" value="{{ old('name', $category->name) }}" required></label>
        <label class="admin-field"><span>Slug</span><input name="slug" value="{{ old('slug', $category->slug) }}"></label>
        <label class="admin-field"><span>Danh mục cha</span><select name="parent_id"><option value="">Không có</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>@endforeach</select></label>
        <label class="admin-field"><span>Thứ tự</span><input type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}"></label>
        <label class="admin-field admin-field--full"><span>Mô tả</span><textarea name="description" rows="5">{{ old('description', $category->description) }}</textarea></label>
        <label class="admin-field admin-field--full"><span>Ảnh danh mục</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp">@if($category->image_path)<img src="{{ asset('storage/'.$category->image_path) }}" class="admin-category-preview" alt="{{ $category->name }}"><span><input type="checkbox" name="remove_image" value="1"> Xóa ảnh hiện tại</span>@endif</label>
        <label class="admin-toggle admin-field--full"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))><span></span><b>Đang hoạt động</b></label>
        <button class="admin-button admin-button--primary">Lưu thay đổi</button>
    </form></section>
@endsection
