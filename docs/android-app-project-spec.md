# PROMPT / PROJECT SPECIFICATION — SHOPKEY ANDROID APP

Sao chép toàn bộ nội dung bên dưới và gửi cho Codex tại thư mục gốc của dự án ShopKey.

---

Bạn là kỹ sư Android cấp cao chịu trách nhiệm xây dựng hoàn chỉnh ứng dụng Android native cho hệ thống ShopKey hiện có. Hãy trực tiếp khảo sát repository, lập kế hoạch ngắn gọn, triển khai, chạy kiểm thử và sửa lỗi đến khi dự án Android build thành công. Không chỉ tạo giao diện mẫu hoặc mã giả.

## 1. Bối cảnh dự án

- Repository hiện tại là backend Laravel 12 và website ShopKey.
- Backend, website khách hàng và Admin đã hoạt động.
- REST API được đặt dưới `/api/v1`.
- Tài liệu chuẩn của API nằm tại `docs/openapi.yaml`.
- Tài liệu đọc nhanh trong Admin nằm tại `/admin/api-docs`.
- Backend hiện có 63 API operation dành cho App.
- API trả JSON theo envelope thống nhất:

```json
{
  "success": true,
  "message": "...",
  "data": {},
  "meta": {}
}
```

- Lỗi API có dạng:

```json
{
  "success": false,
  "message": "...",
  "errors": {
    "field": ["Thông báo lỗi"]
  }
}
```

- Endpoint bảo vệ sử dụng header `Authorization: Bearer <token>`.
- Giá tiền là số nguyên VND, không dùng số thực.
- Sản phẩm là sản phẩm số; dữ liệu bàn giao trong chi tiết đơn hàng là thông tin nhạy cảm.
- Checkout hiện thanh toán bằng ví ShopKey. Người dùng nạp ví bằng thẻ cào hoặc chuyển khoản ngân hàng.

## 2. Mục tiêu

Tạo ứng dụng Android production-grade có đầy đủ chức năng khách hàng tương ứng với website và toàn bộ API hiện có. App phải có giao diện tiếng Việt, hỗ trợ sáng/tối, responsive trên điện thoại và tablet, xử lý tốt loading/empty/error/offline, bảo mật token và không làm thay đổi hoặc phá vỡ backend hiện tại.

Tạo dự án Android trong thư mục:

```text
android-app/
```

Không đặt source Android lẫn vào `app/` của Laravel.

## 3. Nền tảng và kiến trúc bắt buộc

Sử dụng Android native:

- Kotlin.
- Jetpack Compose và Material 3.
- Gradle Kotlin DSL.
- Single-activity architecture.
- Navigation Compose.
- MVVM hoặc unidirectional data flow rõ ràng.
- Coroutines và Flow.
- Dependency injection bằng Hilt.
- Retrofit + OkHttp.
- Kotlin Serialization hoặc Moshi; chọn một giải pháp và dùng nhất quán.
- Room cho dữ liệu cache có ích khi offline.
- DataStore cho thiết lập không nhạy cảm.
- Android Keystore cho Bearer token và dữ liệu đăng nhập nhạy cảm.
- Paging 3 cho danh sách phân trang lớn.
- Coil cho hình ảnh.
- WorkManager cho tác vụ nền phù hợp, không polling vô hạn.

Chọn phiên bản thư viện stable và tương thích tại thời điểm triển khai. Không tự ý dùng bản alpha/beta nếu không có lý do cụ thể. Không hardcode secret, token, mật khẩu, domain production hoặc khóa Firebase.

Tổ chức source theo feature hoặc clean modular architecture vừa đủ, tránh over-engineering. Tối thiểu phải tách:

```text
core/network
core/model
core/database
core/datastore
core/security
core/ui
feature/auth
feature/home
feature/catalog
feature/product
feature/cart
feature/checkout
feature/orders
feature/wallet
feature/deposits
feature/notifications
feature/support
feature/wishlist
feature/profile
feature/settings
```

## 4. Cấu hình môi trường

Tạo ít nhất hai build type hoặc product flavor:

- Development: trỏ tới URL backend local có thể cấu hình.
- Production: nhận URL HTTPS từ Gradle property hoặc biến môi trường khi build.

Expose URL bằng `BuildConfig.API_BASE_URL`. Base URL phải kết thúc bằng `/api/v1/` và không được viết lặp ở nhiều nơi.

Tạo file ví dụ, chẳng hạn `local.properties.example` hoặc tài liệu setup, nhưng không commit secret thật. Nếu backend local dùng HTTP, chỉ cho phép cleartext trong bản debug bằng Network Security Config; bản release chỉ chấp nhận HTTPS.

## 5. Nguồn sự thật API

Trước khi viết data layer:

1. Đọc đầy đủ `docs/openapi.yaml`.
2. Đọc `routes/api.php` để đối chiếu method, URI và middleware.
3. Đọc các Laravel API Resource/Request liên quan để xác nhận field và validation.
4. Không tự suy đoán field khi repository đã có định nghĩa.
5. Nếu OpenAPI và code khác nhau, code backend là nguồn sự thật; sửa OpenAPI nếu phát hiện tài liệu sai, nhưng không thay đổi hành vi backend ngoài phạm vi cần thiết nếu chưa có bằng chứng lỗi.

Tạo model DTO riêng với domain model khi việc tách biệt giúp tránh phụ thuộc trực tiếp vào JSON. Hỗ trợ nullable đúng theo API. Không làm app crash khi backend bổ sung field mới.

## 6. Các chức năng và màn hình bắt buộc

### 6.1 Khởi động App

- Splash screen đúng chuẩn Android.
- Gọi `GET /app-config` để lấy tên shop, logo, banner, màu thương hiệu, liên hệ, phiên bản tối thiểu và trạng thái bảo trì.
- Hiển thị màn hình bảo trì khi backend yêu cầu.
- Hiển thị hộp thoại bắt buộc cập nhật khi `force_update=true` hoặc phiên bản thấp hơn mức tối thiểu.
- Cache cấu hình gần nhất để App vẫn khởi động được khi mất mạng, nhưng không bỏ qua trạng thái bắt buộc cập nhật đã nhận gần nhất.
- Gọi `GET /home` cho dữ liệu trang chủ.

### 6.2 Xác thực và tài khoản

- Đăng ký.
- Đăng nhập.
- Đăng xuất token hiện tại.
- Đăng xuất tất cả thiết bị.
- Quên mật khẩu.
- Đặt lại mật khẩu bằng token/deep link phù hợp.
- Gửi lại email xác minh.
- Xử lý liên kết xác minh email.
- Xem danh sách phiên đăng nhập và thu hồi từng phiên.
- Đổi mật khẩu.
- Xem và cập nhật hồ sơ.
- Sau khi nhận HTTP 401: xóa session cục bộ an toàn và chuyển về đăng nhập; không tạo vòng lặp gọi API.
- Sau HTTP 422: ánh xạ lỗi theo field lên đúng ô nhập liệu.
- Tài khoản bị khóa phải hiển thị thông báo backend, không tự đổi nội dung thành lỗi mạng.

### 6.3 Trang chủ và catalog

- Banner và các khối sản phẩm nổi bật, bán chạy, mới và khuyến mãi.
- Danh mục.
- Danh sách sản phẩm có phân trang.
- Tìm kiếm có debounce.
- Gợi ý tìm kiếm.
- Bộ lọc: danh mục, khoảng giá, loại sản phẩm, đánh giá, còn hàng.
- Sắp xếp theo API.
- Sản phẩm trending.
- Sản phẩm vừa xem.
- Chi tiết sản phẩm, ảnh, tồn kho, giá, giá gốc, bảo hành, mô tả và đánh giá.
- Trạng thái hết hàng phải vô hiệu hóa hành động mua.

### 6.4 Yêu thích

- Danh sách yêu thích.
- Thêm/xóa yêu thích từ card sản phẩm và màn hình chi tiết.
- Optimistic UI chỉ được dùng nếu rollback chính xác khi API lỗi.

### 6.5 Giỏ hàng và voucher

- Xem giỏ hàng.
- Thêm sản phẩm.
- Cập nhật số lượng.
- Xóa sản phẩm.
- Áp dụng và gỡ voucher.
- Luôn sử dụng tổng tiền backend trả về; không coi phép tính trên client là kết quả thanh toán chính thức.
- Xử lý trường hợp sản phẩm ngừng bán, danh mục bị tắt hoặc tồn kho thay đổi.

### 6.6 Checkout và đơn hàng

- Hiển thị số dư ví và tổng thanh toán.
- Checkout bằng `payment_method=wallet`.
- Chống bấm thanh toán nhiều lần trên UI.
- Danh sách và chi tiết đơn hàng.
- Hiển thị trạng thái thanh toán, trạng thái đơn và bàn giao sản phẩm số.
- Cho phép sao chép từng credential; che nội dung nhạy cảm theo mặc định và cho người dùng chủ động hiện.
- Không ghi credential, Bearer token hoặc mật khẩu vào Logcat, analytics hay crash report.
- Hủy đơn khi backend cho phép.
- Thanh toán lại đơn thất bại.
- Gửi yêu cầu hoàn tiền, hiển thị trạng thái và phản hồi Admin.

### 6.7 Ví và nạp tiền

- Số dư ví.
- Lịch sử giao dịch có phân trang.
- Danh sách phương thức thanh toán/nạp tiền đang bật.
- Nạp thẻ: nhà mạng, mệnh giá, serial và mã thẻ; che dữ liệu nhạy cảm.
- Lịch sử nạp thẻ.
- Danh sách tài khoản ngân hàng.
- Tạo hóa đơn chuyển khoản.
- Hiển thị QR, số tài khoản, số tiền và nội dung chuyển khoản với nút sao chép.
- Đếm ngược thời hạn hóa đơn.
- Poll trạng thái hóa đơn với khoảng nghỉ hợp lý; dừng khi màn hình không còn active hoặc khi trạng thái đã kết thúc.
- Hủy hóa đơn đang chờ.
- Lịch sử nạp ngân hàng.

### 6.8 Thông báo

- Danh sách thông báo và số chưa đọc.
- Đánh dấu từng thông báo đã đọc.
- Đánh dấu tất cả đã đọc.
- Điều hướng theo dữ liệu notification, ví dụ mở đúng đơn hàng.
- Đăng ký/cập nhật/xóa device token thông qua API.
- Tích hợp Firebase Cloud Messaging theo cấu trúc hoàn chỉnh nếu có `google-services.json` và cấu hình hợp lệ.
- Nếu chưa có Firebase credential, vẫn phải hoàn thiện interface, token registration và hướng dẫn cấu hình; không tạo khóa giả và không làm build debug thất bại chỉ vì thiếu secret production.

### 6.9 Hỗ trợ khách hàng

- Màn hình chat hỗ trợ.
- Tải tối đa 100 tin gần nhất theo API hiện tại.
- Gửi tin nhắn, đánh dấu đã đọc và phân biệt tin của khách/Admin.
- Hiển thị trạng thái cuộc trò chuyện đã đóng và vô hiệu hóa gửi khi backend từ chối.
- Không giả lập realtime nếu backend chưa có WebSocket. Có thể refresh có kiểm soát khi màn hình đang mở.

### 6.10 Đánh giá

- Danh sách đánh giá công khai theo sản phẩm.
- Danh sách đánh giá của tôi.
- Tạo đánh giá từ sản phẩm thuộc đơn hoàn tất.
- Sửa và xóa đánh giá của chính người dùng.
- Hiển thị đúng lỗi đã đánh giá hoặc không có quyền.

### 6.11 Nội dung và cài đặt

- FAQ.
- Điều khoản sử dụng.
- Chính sách bảo mật.
- Chính sách bảo hành.
- Chính sách hoàn tiền.
- Chế độ sáng/tối/hệ thống.
- Thông tin phiên bản App.
- Liên hệ hỗ trợ theo `app-config`.

## 7. Navigation đề xuất

Bottom navigation chính:

1. Trang chủ.
2. Sản phẩm.
3. Giỏ hàng.
4. Đơn hàng.
5. Tài khoản.

Các màn hình con gồm tìm kiếm, chi tiết sản phẩm, checkout, ví, nạp tiền, thông báo, yêu thích, hỗ trợ, đánh giá, phiên đăng nhập, chính sách và cài đặt.

Navigation phải giữ state hợp lý giữa các tab. Không hiển thị màn hình yêu cầu đăng nhập khi chưa có session; thay vào đó điều hướng tới đăng nhập và quay lại đích ban đầu sau khi đăng nhập thành công.

## 8. Thiết kế giao diện

- Dựa trên nhận diện ShopKey hiện có: nền tối gần đen, màu nhấn đỏ, card rõ ràng; đồng thời hỗ trợ light theme.
- Không sao chép cứng HTML/CSS của website vào WebView.
- Xây dựng UI native Compose.
- Tạo design tokens cho màu, typography, spacing, radius và elevation.
- Hỗ trợ font scale, TalkBack, touch target tối thiểu, contrast và content description.
- Chuỗi hiển thị đặt trong Android resources; mặc định tiếng Việt, chuẩn bị cấu trúc cho tiếng Anh.
- Định dạng VND bằng locale Việt Nam.
- Ngày giờ API ISO-8601 phải được parse an toàn và hiển thị theo múi giờ thiết bị.

## 9. Network, cache và xử lý lỗi

- Một OkHttp interceptor thêm `Accept: application/json` và Bearer token khi có.
- Chỉ thêm `Content-Type` khi request thực sự có body.
- Có timeout hợp lý; không retry tự động request tạo giao dịch như checkout hoặc nạp tiền nếu có nguy cơ gửi trùng.
- Chỉ retry có kiểm soát cho GET/idempotent request.
- Parse HTTP 401, 403, 404, 422, 429 và 5xx thành lỗi domain rõ ràng.
- Tôn trọng message và field errors từ backend.
- Có connectivity/offline state nhưng không coi mọi IOException là mất mạng.
- Cache catalog/home theo chiến lược stale-while-revalidate hợp lý.
- Không cache credential bàn giao hoặc dữ liệu thẻ trong Room.
- Không lưu request/response nhạy cảm bằng network logging ở release.
- Network logging debug phải redact `Authorization`, password, token, serial, code và credential.

## 10. Bảo mật

- Token lưu bằng Android Keystore-backed storage.
- Không lưu mật khẩu người dùng.
- Không hardcode API secret.
- Release build bắt buộc HTTPS và tắt cleartext traffic.
- Tắt verbose logging ở release.
- Không đưa secret vào crash analytics.
- Có ProGuard/R8 rules phù hợp cho Retrofit/serialization.
- Không tự triển khai certificate pinning cứng nếu chưa có chiến lược xoay chứng chỉ; ưu tiên TLS đúng chuẩn.
- Credential sản phẩm phải bị che khi chụp màn hình nếu có thể; cân nhắc `FLAG_SECURE` cho màn hình bàn giao và cho phép cấu hình nếu ảnh hưởng UX.
- Mọi thao tác cập nhật/xóa phải dựa vào kết quả server, không tin ID do client tự suy luận.

## 11. Testing bắt buộc

Tạo và chạy:

- Unit test cho repository, use case, mapper, money/date formatter và validation client.
- Test ViewModel cho loading/success/empty/error.
- MockWebServer contract test cho envelope thành công, 401, 422, 429 và malformed JSON.
- Test token interceptor và redaction logging.
- Test pagination.
- Test checkout chống double tap.
- Test polling hóa đơn dừng đúng lifecycle.
- Compose UI test cho các luồng quan trọng: đăng nhập, catalog, giỏ hàng, checkout, chi tiết đơn và nạp ngân hàng.
- Ít nhất một end-to-end smoke flow dùng fake server: đăng nhập → xem sản phẩm → thêm giỏ → checkout → xem đơn.

Trước khi bàn giao phải chạy tối thiểu:

```bash
./gradlew test
./gradlew lint
./gradlew assembleDebug
```

Nếu môi trường có Android SDK/emulator phù hợp, chạy thêm connected tests. Không tuyên bố connected test đã đạt nếu thực tế chưa chạy.

Sau thay đổi backend bất khả kháng, phải chạy lại test Laravel hiện có và xác nhận không regression.

## 12. Tiêu chí nghiệm thu

Chỉ coi công việc hoàn tất khi:

- Dự án trong `android-app/` mở và sync được bằng Android Studio.
- `assembleDebug` thành công.
- Không còn compile error hoặc lint error mức nghiêm trọng.
- Toàn bộ chức năng bắt buộc có màn hình và được nối API thật, không phải TODO/mock cố định.
- Các trạng thái loading, empty, error và retry hoạt động.
- Đăng nhập được lưu an toàn và tự khôi phục khi mở lại App.
- 401 đưa người dùng về đăng nhập đúng cách.
- 422 hiển thị đúng lỗi field.
- Giỏ hàng, checkout, đơn hàng, credential, ví, nạp thẻ và chuyển khoản hoạt động theo backend.
- Tài nguyên nhạy cảm không xuất hiện trong log hoặc storage không mã hóa.
- Dark/light theme hoạt động.
- Có README hướng dẫn cấu hình base URL, Android SDK, Firebase tùy chọn, build debug/release và chạy test.
- Có danh sách rõ ràng những thứ cần chủ dự án cung cấp bên ngoài, ví dụ production domain, Firebase config và signing key; tuyệt đối không tự tạo hoặc commit secret thật.

## 13. Quy trình làm việc yêu cầu đối với Codex

1. Khảo sát repository và đọc toàn bộ chỉ dẫn liên quan trước khi sửa.
2. Kiểm tra xem `android-app/` đã tồn tại hay chưa; nếu có, giữ lại thay đổi hợp lệ và phát triển tiếp, không xóa bừa.
3. Đưa ra kế hoạch triển khai ngắn gọn theo giai đoạn rồi bắt đầu làm ngay.
4. Ưu tiên vertical slice chạy được: nền tảng → auth → catalog → cart/checkout → orders/wallet → các feature còn lại.
5. Kiểm tra build thường xuyên, không để dồn lỗi tới cuối.
6. Không sửa backend chỉ để né việc parse đúng API.
7. Nếu phát hiện lỗi backend có bằng chứng, sửa tối thiểu, thêm test Laravel và cập nhật OpenAPI/Admin docs tương ứng.
8. Không dừng ở scaffold. Tiếp tục cho tới khi mọi tiêu chí có thể kiểm tra trong môi trường hiện tại đều đạt.
9. Nếu bị chặn bởi Android SDK, Firebase credential, signing key hoặc dịch vụ ngoài, hoàn thiện toàn bộ phần không bị chặn, ghi rõ lệnh đã chạy, lỗi nguyên văn cần thiết và chính xác đầu vào còn thiếu.
10. Khi bàn giao, báo cáo ngắn gọn:
   - Chức năng đã hoàn thành.
   - Kiến trúc chính.
   - File quan trọng.
   - Test/build đã chạy và kết quả.
   - Cấu hình bên ngoài còn cần cung cấp.
   - Không tuyên bố hoàn tất những phần chưa kiểm chứng.

Hãy bắt đầu bằng việc kiểm tra `docs/openapi.yaml`, `routes/api.php`, cấu trúc repository và môi trường Android/Java/Gradle hiện có; sau đó triển khai ứng dụng theo specification này.

