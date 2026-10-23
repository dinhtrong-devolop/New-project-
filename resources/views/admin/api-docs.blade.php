@extends('layouts.admin')

@section('title', 'Hướng dẫn API App')
@section('page_title', 'Hướng dẫn API App')

@section('content')
    <div class="admin-page-head">
        <div>
            <span class="admin-eyebrow">TÍCH HỢP ỨNG DỤNG</span>
            <h1>Hướng dẫn API App</h1>
            <p>Thông tin kết nối nhanh dành cho ứng dụng mobile, desktop hoặc dịch vụ bên thứ ba.</p>
        </div>
        <button type="button" class="admin-button admin-button--primary" data-copy-value="{{ $apiBaseUrl }}">
            <x-icon name="bolt" :size="17" /> Sao chép URL gốc
        </button>
    </div>

    <section class="admin-api-overview">
        <div>
            <small>URL API GỐC</small>
            <code>{{ $apiBaseUrl }}</code>
        </div>
        <div>
            <small>ĐỊNH DẠNG DỮ LIỆU</small>
            <strong>JSON / UTF-8</strong>
        </div>
        <div>
            <small>XÁC THỰC</small>
            <strong>Bearer Token</strong>
        </div>
        <div>
            <small>PHIÊN BẢN</small>
            <strong>v1</strong>
        </div>
    </section>

    <section class="admin-api-reference">
        <div class="admin-api-reference__head">
            <div><span class="admin-eyebrow">DANH SÁCH ĐẦY ĐỦ</span><h2>Toàn bộ API dành cho App</h2></div>
            <span><b>{{ collect($endpointGroups)->flatten(1)->count() }}</b> endpoint</span>
        </div>
        @foreach($endpointGroups as $group => $endpoints)
            <details class="admin-panel admin-api-reference__group" @if($loop->first) open @endif>
                <summary><strong>{{ $group }}</strong><span>{{ count($endpoints) }} API <x-icon name="chevron-down" :size="15" /></span></summary>
                <div class="admin-table-wrap"><table class="admin-table admin-api-table">
                    <thead><tr><th>Method</th><th>Endpoint</th><th>Xác thực</th><th>Dữ liệu gửi</th><th>Chức năng</th></tr></thead>
                    <tbody>
                        @foreach($endpoints as [$method, $path, $auth, $input, $description])
                            <tr>
                                <td><span class="admin-api-method admin-api-method--{{ strtolower($method) }}">{{ $method }}</span></td>
                                <td><code>{{ $path }}</code></td>
                                <td><span class="admin-badge admin-badge--{{ $auth ? 'pending' : 'active' }}">{{ $auth ? 'Bearer' : 'Công khai' }}</span></td>
                                <td>{{ $input }}</td>
                                <td>{{ $description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </details>
        @endforeach
    </section>

    <div class="admin-api-layout">
        <aside class="admin-panel admin-api-nav">
            <div class="admin-panel__head"><div><span class="admin-eyebrow">BẮT ĐẦU</span><h2>Luồng tích hợp</h2></div></div>
            <nav>
                <a href="#api-login"><b>1</b><span><strong>Đăng nhập</strong><small>Lấy access token</small></span></a>
                <a href="#api-products"><b>2</b><span><strong>Sản phẩm</strong><small>Lấy danh mục bán hàng</small></span></a>
                <a href="#api-orders"><b>3</b><span><strong>Đơn hàng</strong><small>Lấy đơn của người dùng</small></span></a>
            </nav>
            <div class="admin-api-security">
                <x-icon name="shield" :size="19" />
                <div><strong>Lưu ý bảo mật</strong><p>Chỉ gọi API qua HTTPS trên web thật. Không ghi token vào log hoặc nhúng cố định trong mã nguồn app.</p></div>
            </div>
        </aside>

        <div class="admin-api-content">
            <section class="admin-panel admin-api-endpoint" id="api-login">
                <div class="admin-api-endpoint__head">
                    <div><span class="admin-api-method admin-api-method--post">POST</span><code>/api/v1/login</code></div>
                    <button type="button" data-copy-value="{{ $loginUrl }}">Sao chép URL</button>
                </div>
                <div class="admin-api-endpoint__body">
                    <h2>Đăng nhập và lấy Bearer token</h2>
                    <p>Endpoint công khai. Gửi thông tin tài khoản và tên thiết bị; token trả về dùng cho các API yêu cầu đăng nhập. Giới hạn 10 yêu cầu/phút.</p>

                    <h3>Body JSON</h3>
                    <div class="admin-table-wrap"><table class="admin-table admin-api-table">
                        <thead><tr><th>Trường</th><th>Kiểu</th><th>Bắt buộc</th><th>Mô tả</th></tr></thead>
                        <tbody>
                            <tr><td><code>email</code></td><td>string</td><td>Có</td><td>Email tài khoản, tối đa 255 ký tự</td></tr>
                            <tr><td><code>password</code></td><td>string</td><td>Có</td><td>Mật khẩu tài khoản</td></tr>
                            <tr><td><code>device_name</code></td><td>string</td><td>Có</td><td>Tên thiết bị/app, tối đa 120 ký tự</td></tr>
                        </tbody>
                    </table></div>

                    <x-admin.api-code title="Lệnh cURL" :code="$loginCurl" />

                    <h3>Phản hồi thành công — HTTP 200</h3>
                    <x-admin.api-code title="JSON" :code="$loginResponse" />
                    <div class="admin-api-note"><strong>Sau khi đăng nhập:</strong> lấy <code>data.token</code> và gửi header <code>Authorization: Bearer {token}</code> ở các API bảo vệ.</div>
                </div>
            </section>

            <section class="admin-panel admin-api-endpoint" id="api-products">
                <div class="admin-api-endpoint__head">
                    <div><span class="admin-api-method admin-api-method--get">GET</span><code>/api/v1/products</code></div>
                    <button type="button" data-copy-value="{{ $productsUrl }}">Sao chép URL</button>
                </div>
                <div class="admin-api-endpoint__body">
                    <h2>Lấy danh sách sản phẩm</h2>
                    <p>Endpoint công khai, không cần token. Chỉ trả về sản phẩm đang hoạt động thuộc danh mục đang hoạt động.</p>

                    <h3>Tham số query</h3>
                    <div class="admin-table-wrap"><table class="admin-table admin-api-table">
                        <thead><tr><th>Tham số</th><th>Giá trị</th><th>Mô tả</th></tr></thead>
                        <tbody>
                            <tr><td><code>search</code></td><td>string</td><td>Tìm theo tên, SKU hoặc danh mục</td></tr>
                            <tr><td><code>category</code></td><td>slug</td><td>Lọc theo slug danh mục</td></tr>
                            <tr><td><code>min_price</code>, <code>max_price</code></td><td>integer ≥ 0</td><td>Khoảng giá VND</td></tr>
                            <tr><td><code>type</code></td><td>enum</td><td><code>license_key</code>, <code>account</code>, <code>gift_card</code>, <code>subscription</code>, <code>other</code></td></tr>
                            <tr><td><code>rating</code></td><td>1–5</td><td>Điểm đánh giá tối thiểu</td></tr>
                            <tr><td><code>in_stock</code></td><td>0 hoặc 1</td><td>Chỉ lấy sản phẩm còn hàng khi bằng 1</td></tr>
                            <tr><td><code>sort</code></td><td>enum</td><td><code>featured</code>, <code>best_selling</code>, <code>latest</code>, <code>price_asc</code>, <code>price_desc</code>, <code>rating</code></td></tr>
                            <tr><td><code>page</code></td><td>integer ≥ 1</td><td>Trang hiện tại</td></tr>
                            <tr><td><code>per_page</code></td><td>1–50</td><td>Số sản phẩm mỗi trang, mặc định 12</td></tr>
                        </tbody>
                    </table></div>

                    <x-admin.api-code title="Lệnh cURL" :code="$productsCurl" />

                    <h3>Phản hồi thành công — HTTP 200</h3>
                    <x-admin.api-code title="JSON rút gọn" :code="$productsResponse" />
                </div>
            </section>

            <section class="admin-panel admin-api-endpoint" id="api-orders">
                <div class="admin-api-endpoint__head">
                    <div><span class="admin-api-method admin-api-method--get">GET</span><code>/api/v1/orders</code></div>
                    <button type="button" data-copy-value="{{ $ordersUrl }}">Sao chép URL</button>
                </div>
                <div class="admin-api-endpoint__body">
                    <h2>Lấy danh sách đơn hàng</h2>
                    <p>Yêu cầu Bearer token. API chỉ trả về đơn hàng thuộc tài khoản đang đăng nhập, mới nhất trước. Giới hạn 60 yêu cầu/phút.</p>

                    <h3>Headers và query</h3>
                    <div class="admin-table-wrap"><table class="admin-table admin-api-table">
                        <thead><tr><th>Trường</th><th>Bắt buộc</th><th>Mô tả</th></tr></thead>
                        <tbody>
                            <tr><td><code>Authorization</code></td><td>Có</td><td><code>Bearer YOUR_ACCESS_TOKEN</code></td></tr>
                            <tr><td><code>Accept</code></td><td>Nên dùng</td><td><code>application/json</code></td></tr>
                            <tr><td><code>page</code></td><td>Không</td><td>Trang hiện tại, mặc định 1</td></tr>
                            <tr><td><code>per_page</code></td><td>Không</td><td>1–50 đơn/trang, mặc định 15</td></tr>
                        </tbody>
                    </table></div>

                    <x-admin.api-code title="Lệnh cURL" :code="$ordersCurl" />

                    <h3>Phản hồi thành công — HTTP 200</h3>
                    <x-admin.api-code title="JSON rút gọn" :code="$ordersResponse" />
                    <div class="admin-api-errors">
                        <strong>Mã lỗi thường gặp</strong>
                        <span><b>401</b> Token thiếu, sai hoặc hết hiệu lực</span>
                        <span><b>422</b> Dữ liệu gửi lên không hợp lệ</span>
                        <span><b>429</b> Gửi quá nhiều yêu cầu trong một phút</span>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
