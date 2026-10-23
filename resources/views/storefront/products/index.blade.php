@extends('layouts.storefront')

@section('title', 'Tất cả sản phẩm - ShopKey')

@section('content')
    <div class="page-hero page-hero--catalog">
        <div class="container">
            <nav class="breadcrumb"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><strong>Sản phẩm</strong></nav>
            <div class="page-hero__content">
                <div><span class="section-heading__eyebrow section-heading__eyebrow--light">SHOPKEY CATALOG</span><h1>Khám phá sản phẩm số</h1><p>Key, phần mềm, gift card và gói đăng ký rõ nguồn gốc, sẵn sàng giao ngay.</p></div>
                <div class="page-hero__metric"><strong>{{ number_format($products->total()) }}</strong><span>sản phẩm phù hợp</span></div>
            </div>
        </div>
    </div>

    <section class="section catalog-page">
        <div class="container">
            <div class="catalog-toolbar">
                <button type="button" class="button button--outline catalog-filter-toggle" data-filter-toggle><x-icon name="filter" :size="18" /> Bộ lọc</button>
                <p>Hiển thị <strong>{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</strong> trong {{ number_format($products->total()) }} sản phẩm</p>
                <label class="sort-select">Sắp xếp:
                    <select data-sort-select>
                        <option value="featured" @selected(request('sort', 'featured') === 'featured')>Nổi bật</option>
                        <option value="best_selling" @selected(request('sort') === 'best_selling')>Bán chạy nhất</option>
                        <option value="latest" @selected(request('sort') === 'latest')>Mới nhất</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Giá thấp đến cao</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Giá cao đến thấp</option>
                        <option value="rating" @selected(request('sort') === 'rating')>Đánh giá cao nhất</option>
                    </select>
                </label>
            </div>

            <div class="catalog-layout">
                <aside class="filter-panel" data-filter-panel>
                    <div class="filter-panel__head"><h2>Bộ lọc sản phẩm</h2><button type="button" data-filter-close aria-label="Đóng"><x-icon name="close" :size="21" /></button></div>
                    <form action="{{ route('products.index') }}" method="GET">
                        <input type="hidden" name="sort" value="{{ request('sort', 'featured') }}">
                        <div class="filter-group">
                            <label for="catalog-search">Từ khóa</label>
                            <div class="filter-search"><x-icon name="search" :size="17" /><input id="catalog-search" type="search" name="search" value="{{ request('search') }}" placeholder="Tên hoặc mã sản phẩm"></div>
                        </div>
                        <div class="filter-group">
                            <label>Danh mục</label>
                            <div class="filter-options">
                                <label><input type="radio" name="category" value="" @checked(!request('category'))><span>Tất cả danh mục</span></label>
                                @foreach($categories as $category)
                                    <label><input type="radio" name="category" value="{{ $category->slug }}" @checked(request('category') === $category->slug)><span>{{ $category->name }}</span></label>
                                @endforeach
                            </div>
                        </div>
                        <div class="filter-group">
                            <label>Khoảng giá (VND)</label>
                            <div class="price-range"><input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Từ"><span>—</span><input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Đến"></div>
                        </div>
                        <div class="filter-group">
                            <label for="product-type">Loại sản phẩm</label>
                            <select id="product-type" name="type">
                                <option value="">Tất cả loại</option>
                                @foreach($productTypes as $type)
                                    <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="rating">Đánh giá tối thiểu</label>
                            <select id="rating" name="rating">
                                <option value="">Tất cả đánh giá</option>
                                @foreach([5, 4, 3] as $rating)
                                    <option value="{{ $rating }}" @selected((int) request('rating') === $rating)>{{ $rating }} sao trở lên</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="switch-row"><input type="checkbox" name="in_stock" value="1" @checked(request()->boolean('in_stock'))><span></span> Chỉ hiển thị còn hàng</label>
                        <button type="submit" class="button button--primary button--full">Áp dụng bộ lọc</button>
                        <a href="{{ route('products.index') }}" class="filter-reset">Xóa tất cả bộ lọc</a>
                    </form>
                </aside>

                <div class="catalog-results">
                    <div class="product-grid product-grid--three">
                        @forelse($products as $product)
                            <x-product-card :product="$product" />
                        @empty
                            <div class="empty-state empty-state--wide"><x-icon name="search" :size="32" /><strong>Không tìm thấy sản phẩm</strong><p>Hãy thử thay đổi từ khóa hoặc bộ lọc.</p><a href="{{ route('products.index') }}" class="button button--primary">Xem toàn bộ</a></div>
                        @endforelse
                    </div>
                    @if($products->hasPages())
                        <div class="pagination-wrap">{{ $products->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
