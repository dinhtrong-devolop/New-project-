@extends('layouts.admin')

@section('title', $product->exists ? 'Sửa sản phẩm' : 'Thêm sản phẩm')
@section('page_title', $product->exists ? 'Chỉnh sửa sản phẩm' : 'Tạo sản phẩm')

@section('content')
    <div class="admin-page-head"><div><span class="admin-eyebrow">CATALOG EDITOR</span><h1>{{ $product->exists ? $product->name : 'Sản phẩm mới' }}</h1><p>Thông tin này được sử dụng trực tiếp trên storefront ShopKey.</p></div><a href="{{ route('admin.products.index') }}" class="admin-button"><x-icon name="arrow-right" class="icon--reverse" :size="17" /> Quay lại</a></div>
    <form action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="admin-form-layout">
        @csrf @if($product->exists) @method('PUT') @endif
        <section class="admin-panel admin-form-card"><div class="admin-form-card__head"><h2>Thông tin cơ bản</h2><p>Tên, mã định danh và nội dung hiển thị.</p></div><div class="admin-form-grid">
            <label class="admin-field admin-field--full"><span>Tên sản phẩm</span><input name="name" value="{{ old('name', $product->name) }}" required></label>
            <label class="admin-field"><span>Slug</span><input name="slug" value="{{ old('slug', $product->slug) }}" placeholder="Tự tạo từ tên"></label>
            <label class="admin-field"><span>SKU</span><input name="sku" value="{{ old('sku', $product->sku) }}" required></label>
            <label class="admin-field"><span>Danh mục</span><select name="category_id" required><option value="">Chọn danh mục</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="admin-field"><span>Loại sản phẩm</span><select name="product_type">@foreach(App\Enums\ProductType::cases() as $type)<option value="{{ $type->value }}" @selected(old('product_type', $product->product_type?->value ?? 'license_key') === $type->value)>{{ $type->label() }}</option>@endforeach</select></label>
            <label class="admin-field admin-field--full"><span>Mô tả ngắn</span><textarea name="short_description" rows="2">{{ old('short_description', $product->short_description) }}</textarea></label>
            <label class="admin-field admin-field--full"><span>Mô tả chi tiết</span><textarea name="description" rows="7">{{ old('description', $product->description) }}</textarea></label>
        </div></section>
        <aside class="admin-form-side">
            <section class="admin-panel admin-form-card"><div class="admin-form-card__head"><h2>Giá & trạng thái</h2></div><div class="admin-form-grid admin-form-grid--one">
                <label class="admin-field"><span>Giá bán (VND)</span><input type="number" min="0" name="price" value="{{ old('price', $product->price) }}" required></label>
                <label class="admin-field"><span>Giá gốc (VND)</span><input type="number" min="0" name="original_price" value="{{ old('original_price', $product->original_price) }}"></label>
                <label class="admin-field"><span>Trạng thái</span><select name="status">@foreach(App\Enums\ProductStatus::manageableCases() as $status)<option value="{{ $status->value }}" @selected(old('status', $product->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
                <label class="admin-field"><span>Giao hàng</span><select name="delivery_type">@foreach(App\Enums\DeliveryType::cases() as $delivery)<option value="{{ $delivery->value }}" @selected(old('delivery_type', $product->delivery_type?->value ?? 'automatic') === $delivery->value)>{{ $delivery->label() }}</option>@endforeach</select></label>
                <label class="admin-field"><span>Bảo hành (ngày)</span><input type="number" min="0" name="warranty_days" value="{{ old('warranty_days', $product->warranty_days ?? 0) }}"></label>
                <label class="admin-toggle"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))><span></span><b>Sản phẩm nổi bật</b></label>
            </div></section>
            <section class="admin-panel admin-form-card"><div class="admin-form-card__head"><h2>Hình ảnh sản phẩm</h2><p>JPG, PNG hoặc WebP; tối đa 5 ảnh, mỗi ảnh 4MB.</p></div><div class="admin-form-grid admin-form-grid--one">
                <label class="admin-field"><span>Tải ảnh mới</span><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple></label>
                @if($product->exists && $product->images->isNotEmpty())<div class="admin-image-list">@foreach($product->images as $image)<label><img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text }}"><span><input type="checkbox" name="remove_images[]" value="{{ $image->id }}"> Xóa ảnh</span></label>@endforeach</div>@endif
            </div></section>
            <button type="submit" class="admin-button admin-button--primary admin-button--wide"><x-icon name="check" :size="18" /> {{ $product->exists ? 'Lưu thay đổi' : 'Tạo sản phẩm' }}</button>
        </aside>
    </form>
@endsection
