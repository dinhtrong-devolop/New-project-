<?php

return [
    'min_supported_version' => env('MOBILE_MIN_SUPPORTED_VERSION', '1.0.0'),
    'latest_version' => env('MOBILE_LATEST_VERSION', '1.0.0'),
    'force_update' => (bool) env('MOBILE_FORCE_UPDATE', false),
    'maintenance_message' => env('MOBILE_MAINTENANCE_MESSAGE', 'Hệ thống đang bảo trì, vui lòng quay lại sau.'),
    'pages' => [
        'terms' => [
            'title' => 'Điều khoản sử dụng',
            'content' => 'Người dùng có trách nhiệm bảo mật tài khoản, kiểm tra kỹ thông tin sản phẩm và tuân thủ quy định sử dụng của từng sản phẩm số.',
        ],
        'privacy' => [
            'title' => 'Chính sách bảo mật',
            'content' => 'Thông tin cá nhân chỉ được sử dụng để vận hành tài khoản, xử lý giao dịch và hỗ trợ khách hàng theo quy định pháp luật.',
        ],
        'warranty' => [
            'title' => 'Chính sách bảo hành',
            'content' => 'Thời hạn bảo hành được ghi trên từng sản phẩm. Khách hàng cần cung cấp mã đơn hàng khi yêu cầu hỗ trợ.',
        ],
        'refund' => [
            'title' => 'Chính sách hoàn tiền',
            'content' => 'Yêu cầu hoàn tiền được xem xét theo trạng thái bàn giao, thời hạn bảo hành và nguyên nhân lỗi của sản phẩm.',
        ],
    ],
    'faqs' => [
        ['question' => 'Sản phẩm được giao như thế nào?', 'answer' => 'Sản phẩm số được bàn giao tự động trong chi tiết đơn hàng sau khi thanh toán thành công.'],
        ['question' => 'Tôi có thể nạp tiền bằng cách nào?', 'answer' => 'Bạn có thể nạp bằng thẻ cào hoặc chuyển khoản ngân hàng qua các phương thức đang được bật.'],
        ['question' => 'Làm sao để được hỗ trợ?', 'answer' => 'Sử dụng mục Hỗ trợ trong App và gửi nội dung kèm mã đơn hàng nếu có.'],
    ],
];
