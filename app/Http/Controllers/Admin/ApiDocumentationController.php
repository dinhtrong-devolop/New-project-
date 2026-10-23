<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ApiDocumentationController extends Controller
{
    public function __invoke(): View
    {
        $loginUrl = route('api.v1.login');
        $productsUrl = route('api.v1.products.index');
        $ordersUrl = route('api.v1.orders.index');

        return view('admin.api-docs', [
            'apiBaseUrl' => url('/api/v1'),
            'loginUrl' => $loginUrl,
            'productsUrl' => $productsUrl,
            'ordersUrl' => $ordersUrl,
            'loginCurl' => <<<CURL
curl --request POST "{$loginUrl}" \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --data '{"email":"user@example.com","password":"your-password","device_name":"mobile-app"}'
CURL,
            'productsCurl' => <<<CURL
curl --request GET "{$productsUrl}?sort=latest&in_stock=1&page=1&per_page=12" \\
  --header "Accept: application/json"
CURL,
            'ordersCurl' => <<<CURL
curl --request GET "{$ordersUrl}?page=1&per_page=15" \\
  --header "Accept: application/json" \\
  --header "Authorization: Bearer YOUR_ACCESS_TOKEN"
CURL,
            'loginResponse' => <<<'JSON'
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { "id": 1, "name": "Nguyễn Văn A", "email": "user@example.com" },
    "token": "1|access-token...",
    "token_type": "Bearer"
  }
}
JSON,
            'productsResponse' => <<<'JSON'
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Tên sản phẩm",
      "slug": "ten-san-pham",
      "product_type": "license_key",
      "price": 199000,
      "stock": 10,
      "rating_average": 5
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 12, "total": 1 }
}
JSON,
            'ordersResponse' => <<<'JSON'
{
  "success": true,
  "message": "Orders retrieved successfully",
  "data": [
    {
      "id": 10,
      "order_code": "SK-ABC123",
      "subtotal": 199000,
      "discount_amount": 0,
      "total": 199000,
      "payment_status": "paid",
      "order_status": "completed",
      "items_count": 1,
      "created_at": "2026-09-14T08:00:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "total": 1 }
}
JSON,
            'endpointGroups' => $this->endpointGroups(),
        ]);
    }

    private function endpointGroups(): array
    {
        return [
            'Khởi động và nội dung' => [
                ['GET', '/api/v1/app-config', false, '—', 'Logo, banner, màu sắc, phiên bản và trạng thái App'],
                ['GET', '/api/v1/home', false, '—', 'Dữ liệu tổng hợp cho màn hình trang chủ'],
                ['GET', '/api/v1/categories', false, '—', 'Danh sách danh mục đang hoạt động'],
                ['GET', '/api/v1/products', false, 'query: search, category, price, type, sort, page', 'Danh sách sản phẩm và bộ lọc'],
                ['GET', '/api/v1/products/{slug}', false, '—', 'Chi tiết sản phẩm; có token sẽ lưu lịch sử xem'],
                ['GET', '/api/v1/products/trending', false, 'query: limit (1–30)', 'Sản phẩm bán chạy và đánh giá cao'],
                ['GET', '/api/v1/search/suggestions', false, 'query: q (tối thiểu 2 ký tự)', 'Gợi ý sản phẩm và danh mục'],
                ['GET', '/api/v1/products/{slug}/reviews', false, 'query: page, per_page', 'Đánh giá công khai của sản phẩm'],
                ['GET', '/api/v1/pages/{slug}', false, 'terms, privacy, warranty, refund', 'Chi tiết trang nội dung'],
                ['GET', '/api/v1/faqs', false, '—', 'Câu hỏi thường gặp'],
                ['GET', '/api/v1/policies', false, '—', 'Danh sách chính sách'],
            ],
            'Tài khoản và phiên đăng nhập' => [
                ['POST', '/api/v1/register', false, 'name, email, phone?, password, password_confirmation, terms, device_name?', 'Đăng ký và nhận Bearer token'],
                ['POST', '/api/v1/login', false, 'email, password, device_name', 'Đăng nhập và nhận Bearer token'],
                ['POST', '/api/v1/forgot-password', false, 'email', 'Gửi email đặt lại mật khẩu'],
                ['POST', '/api/v1/reset-password', false, 'token, email, password, password_confirmation', 'Đặt lại mật khẩu'],
                ['GET', '/api/v1/email/verify/{id}/{hash}', false, 'URL có chữ ký gửi qua email', 'Xác minh địa chỉ email'],
                ['POST', '/api/v1/email/verification-notification', true, '—', 'Gửi lại email xác minh'],
                ['POST', '/api/v1/logout', true, '—', 'Thu hồi token hiện tại'],
                ['POST', '/api/v1/logout-all', true, '—', 'Thu hồi tất cả token'],
                ['GET', '/api/v1/sessions', true, '—', 'Danh sách phiên/token đăng nhập'],
                ['DELETE', '/api/v1/sessions/{token_id}', true, '—', 'Thu hồi một phiên đăng nhập'],
                ['PUT', '/api/v1/change-password', true, 'current_password, password, password_confirmation', 'Đổi mật khẩu và thu hồi token khác'],
                ['GET', '/api/v1/profile', true, '—', 'Thông tin tài khoản và số dư'],
                ['PUT', '/api/v1/profile', true, 'name, email, phone?', 'Cập nhật hồ sơ'],
            ],
            'Giỏ hàng và đơn hàng' => [
                ['GET', '/api/v1/cart', true, '—', 'Chi tiết giỏ hàng'],
                ['POST', '/api/v1/cart', true, 'product_id, quantity', 'Thêm sản phẩm vào giỏ'],
                ['PATCH', '/api/v1/cart/{item}', true, 'quantity', 'Cập nhật số lượng'],
                ['DELETE', '/api/v1/cart/{item}', true, '—', 'Xóa sản phẩm khỏi giỏ'],
                ['POST', '/api/v1/cart/voucher', true, 'voucher', 'Áp dụng voucher'],
                ['DELETE', '/api/v1/cart/voucher', true, '—', 'Gỡ voucher'],
                ['POST', '/api/v1/checkout', true, 'payment_method=wallet, customer_note?', 'Thanh toán và bàn giao tự động'],
                ['GET', '/api/v1/orders', true, 'query: page, per_page', 'Danh sách đơn của tài khoản'],
                ['GET', '/api/v1/orders/{order_code}', true, '—', 'Chi tiết đơn và dữ liệu bàn giao'],
                ['POST', '/api/v1/orders/{order_code}/cancel', true, '—', 'Hủy đơn chưa thanh toán/chưa bàn giao'],
                ['POST', '/api/v1/orders/{order_code}/retry-payment', true, '—', 'Thanh toán lại đơn lỗi bằng ví'],
                ['POST', '/api/v1/orders/{order_code}/refund-request', true, 'reason', 'Gửi yêu cầu hoàn tiền'],
                ['GET', '/api/v1/payment-methods', true, '—', 'Phương thức checkout và nạp tiền đang bật'],
            ],
            'Ví và nạp tiền' => [
                ['GET', '/api/v1/wallet', true, '—', 'Số dư ví'],
                ['GET', '/api/v1/wallet/transactions', true, 'query: page, per_page', 'Lịch sử biến động số dư'],
                ['GET', '/api/v1/deposits', true, 'query: page, per_page', 'Lịch sử nạp thẻ'],
                ['POST', '/api/v1/deposits/card', true, 'provider, amount, serial, code', 'Gửi thẻ cào'],
                ['GET', '/api/v1/bank-accounts', true, '—', 'Tài khoản ngân hàng nhận tiền'],
                ['GET', '/api/v1/deposits/bank', true, 'query: page, per_page', 'Lịch sử nạp tiền ngân hàng'],
                ['POST', '/api/v1/deposits/bank', true, 'bank_account_id, amount', 'Tạo hóa đơn chuyển khoản và QR'],
                ['GET', '/api/v1/deposits/bank/{id}', true, '—', 'Chi tiết hóa đơn chuyển khoản'],
                ['GET', '/api/v1/deposits/bank/{id}/status', true, '—', 'Kiểm tra trạng thái hóa đơn'],
                ['POST', '/api/v1/deposits/bank/{id}/cancel', true, '—', 'Hủy hóa đơn đang chờ'],
            ],
            'Thông báo và thiết bị' => [
                ['GET', '/api/v1/notifications', true, 'query: page, per_page', 'Danh sách và số thông báo chưa đọc'],
                ['PATCH', '/api/v1/notifications/{id}/read', true, '—', 'Đánh dấu một thông báo đã đọc'],
                ['POST', '/api/v1/notifications/read-all', true, '—', 'Đánh dấu tất cả đã đọc'],
                ['POST', '/api/v1/devices', true, 'token, platform, device_name?', 'Đăng ký token push Android/iOS/Web'],
                ['DELETE', '/api/v1/devices/{id}', true, '—', 'Gỡ thiết bị push'],
            ],
            'Hỗ trợ, yêu thích và đánh giá' => [
                ['GET', '/api/v1/support/messages', true, '—', 'Cuộc trò chuyện và 100 tin gần nhất'],
                ['POST', '/api/v1/support/messages', true, 'message', 'Gửi tin nhắn hỗ trợ'],
                ['POST', '/api/v1/support/read', true, '—', 'Đánh dấu phản hồi hỗ trợ đã đọc'],
                ['GET', '/api/v1/wishlist', true, 'query: page, per_page', 'Danh sách sản phẩm yêu thích'],
                ['POST', '/api/v1/wishlist/{product_slug}', true, '—', 'Thêm vào yêu thích'],
                ['DELETE', '/api/v1/wishlist/{product_slug}', true, '—', 'Xóa khỏi yêu thích'],
                ['GET', '/api/v1/products/recently-viewed', true, 'query: limit (1–50)', 'Sản phẩm vừa xem'],
                ['GET', '/api/v1/reviews/mine', true, 'query: page, per_page', 'Đánh giá của tài khoản'],
                ['POST', '/api/v1/reviews', true, 'order_item_id, rating, comment?', 'Đánh giá sản phẩm đã mua'],
                ['PUT', '/api/v1/reviews/{id}', true, 'rating, comment?', 'Sửa đánh giá của chính mình'],
                ['DELETE', '/api/v1/reviews/{id}', true, '—', 'Xóa đánh giá của chính mình'],
            ],
        ];
    }
}
