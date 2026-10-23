# PROMPT / PROJECT SPECIFICATION — SHOPKEY iOS APP

Sao chép toàn bộ nội dung bên dưới và gửi cho Codex tại thư mục gốc của dự án ShopKey.

---

Bạn là kỹ sư iOS cấp cao chịu trách nhiệm xây dựng hoàn chỉnh ứng dụng iOS native cho hệ thống ShopKey hiện có. Hãy trực tiếp khảo sát repository, lập kế hoạch ngắn gọn, triển khai, chạy kiểm thử và sửa lỗi đến khi dự án iOS build thành công trong môi trường macOS/Xcode phù hợp. Không chỉ tạo giao diện mẫu, mã giả hoặc scaffold chưa nối API.

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
- Việc phân phối ứng dụng qua App Store có ràng buộc riêng đối với hàng hóa số, thanh toán ngoài ứng dụng và xóa tài khoản. Không được giả định rằng luồng thanh toán hiện tại của website/Android có thể phát hành nguyên trạng trên mọi storefront của App Store.

## 2. Mục tiêu

Tạo ứng dụng iOS production-grade có đầy đủ chức năng khách hàng tương ứng với website và toàn bộ API hiện có, đồng thời có chế độ thương mại phù hợp quy định phân phối thực tế. App phải có giao diện tiếng Việt, hỗ trợ sáng/tối, responsive trên iPhone và iPad, xử lý tốt loading/empty/error/offline, bảo mật token và không phá vỡ backend hiện tại.

Tạo dự án iOS trong thư mục:

```text
ios-app/
```

Không đặt source iOS lẫn vào `app/` của Laravel. Dự án phải mở trực tiếp được bằng Xcode và có shared scheme để CI có thể build/test.

## 3. Nền tảng và kiến trúc bắt buộc

Sử dụng iOS native:

- Swift và SwiftUI.
- Phiên bản stable của Xcode, Swift và iOS SDK tại thời điểm triển khai.
- Chọn deployment target còn được Apple và thư viện sử dụng hỗ trợ; ghi rõ quyết định trong README.
- App lifecycle bằng SwiftUI, không tạo UIKit shell không cần thiết.
- `NavigationStack`/`NavigationSplitView` và điều hướng type-safe.
- MVVM hoặc unidirectional data flow rõ ràng.
- Swift Concurrency: `async/await`, `Task`, actor và `AsyncSequence` khi phù hợp.
- Observation framework hoặc `ObservableObject`; chọn nhất quán theo deployment target.
- `URLSession` + `Codable` cho network/JSON; chỉ thêm thư viện HTTP khi có lý do rõ ràng.
- Keychain Services cho Bearer token và dữ liệu xác thực nhạy cảm.
- SwiftData hoặc Core Data cho cache offline; chọn một giải pháp tương thích deployment target.
- `UserDefaults`/`AppStorage` chỉ cho thiết lập không nhạy cảm.
- `URLCache` hoặc image pipeline có cache hợp lý; nếu dùng thư viện ảnh, quản lý bằng Swift Package Manager.
- `BackgroundTasks` chỉ cho công việc nền phù hợp, không polling vô hạn.
- XCTest/Swift Testing và XCUITest theo mức hỗ trợ stable của toolchain đã chọn.
- Hạn chế dependency bên thứ ba; mọi dependency phải có lý do, phiên bản pin hợp lý và license phù hợp.

Tổ chức source theo feature hoặc clean architecture vừa đủ, tránh over-engineering. Tối thiểu nên có cấu trúc tương đương:

```text
ios-app/
  ShopKey.xcodeproj
  ShopKey/
    App/
    Core/Network/
    Core/Models/
    Core/Persistence/
    Core/Security/
    Core/DesignSystem/
    Core/Utilities/
    Features/Auth/
    Features/Home/
    Features/Catalog/
    Features/ProductDetail/
    Features/Wishlist/
    Features/Cart/
    Features/Checkout/
    Features/Orders/
    Features/Wallet/
    Features/Deposits/
    Features/Notifications/
    Features/Support/
    Features/Reviews/
    Features/Profile/
    Features/Settings/
  ShopKeyTests/
  ShopKeyUITests/
  Config/
```

Không hardcode secret, token, mật khẩu, production domain, Apple key, Firebase key, signing identity hoặc Team ID.

## 4. Cấu hình môi trường và ký ứng dụng

- Tạo cấu hình Debug và Release bằng `.xcconfig` hoặc cơ chế tương đương.
- Development nhận URL backend local có thể cấu hình.
- Production nhận URL HTTPS từ build setting/CI secret.
- Expose một nguồn `API_BASE_URL` duy nhất cho app. Base URL phải kết thúc bằng `/api/v1/` và không được viết lặp ở nhiều nơi.
- Commit file cấu hình ví dụ nhưng không commit secret thật.
- Debug có thể cho phép HTTP tới host local cụ thể bằng ATS exception tối thiểu. Release không cho phép arbitrary loads và chỉ dùng HTTPS.
- Bundle identifier, Development Team, provisioning profile và signing certificate phải cấu hình từ Xcode/CI; không tự tạo giá trị production giả.
- Debug build trên simulator phải có thể chạy với `CODE_SIGNING_ALLOWED=NO` khi phù hợp.
- Tách rõ cấu hình capability: Push Notifications, Associated Domains, Keychain Sharing và Background Modes. Chỉ bật capability thực sự cần.

## 5. Nguồn sự thật API

Trước khi viết data layer:

1. Đọc đầy đủ `docs/openapi.yaml`.
2. Đọc `routes/api.php` để đối chiếu method, URI và middleware.
3. Đọc các Laravel API Resource/Request liên quan để xác nhận field, kiểu dữ liệu và validation.
4. Kiểm tra response thực tế bằng test backend hoặc request an toàn nếu môi trường cho phép.
5. Không tự suy đoán field khi repository đã có định nghĩa.
6. Nếu OpenAPI và code khác nhau, code backend là nguồn sự thật; sửa OpenAPI khi có bằng chứng tài liệu sai, nhưng không tự đổi hành vi backend để né việc parse đúng API.

Tách DTO khỏi domain model khi giúp giảm phụ thuộc vào JSON. Xử lý nullable đúng theo API, decoding có ngữ cảnh và không làm app crash khi backend bổ sung field mới. Dùng `Decimal` hoặc số nguyên minor unit phù hợp; không dùng `Double` để quyết định số tiền.

## 6. Cổng tuân thủ App Store bắt buộc

ShopKey bán software key, game key, gift card, subscription và tài khoản số. Đây là vùng có rủi ro review cao. Trước khi hoàn thiện hoặc bật bất kỳ luồng mua/nạp tiền nào trong bản phân phối App Store, Codex phải:

1. Đọc phiên bản hiện hành của Apple App Review Guidelines, đặc biệt mục 3.1 về Payments, tại thời điểm triển khai và trước khi phát hành.
2. Không lách review, không che chức năng với reviewer, không dùng remote config để bí mật bật luồng bị cấm sau review và không viết hướng dẫn đánh lừa người dùng/reviewer.
3. Tạo `ios-app/APP_STORE_COMMERCE_DECISION.md`, ghi ngày kiểm tra, loại hàng hóa, storefront/phương thức phân phối dự kiến, entitlement áp dụng, luồng được bật và bằng chứng cho quyết định.
4. Thiết kế commerce mode rõ ràng, ví dụ:

```swift
enum CommerceMode {
    case catalogOnly
    case storeKit
    case externalPurchaseAllowed
}
```

Mode phải được chọn minh bạch bằng Release configuration và/hoặc cấu hình backend đã kiểm soát; mặc định an toàn khi chưa có quyết định pháp lý/review phù hợp.

- `catalogOnly`: cho xem catalog/tài khoản/đơn đã có nhưng ẩn hoặc vô hiệu hóa hành động mua/nạp không hợp lệ, kèm nội dung trung tính và đúng quy định.
- `storeKit`: dùng StoreKit 2 cho sản phẩm đủ điều kiện, restore purchase, transaction updates và xác minh server-side. Không chỉ tin kết quả client.
- `externalPurchaseAllowed`: chỉ dùng khi phương thức phân phối, storefront và entitlement hiện hành thực sự cho phép; triển khai đúng wording, disclosure và API Apple yêu cầu.

Nếu phải dùng StoreKit 2:

- Tạo StoreKit product mapping rõ ràng, không hardcode tản mát.
- Có `.storekit` configuration cho development/test, không giả mạo product production.
- Backend hiện tại chưa được coi là có xác minh giao dịch Apple. Nếu cần, bổ sung tối thiểu endpoint/service để xác minh với App Store Server API, xử lý App Store Server Notifications, replay/idempotency, refund/revoke và ánh xạ giao dịch tới order/user.
- Không giao credential chỉ dựa trên callback từ thiết bị.
- Mọi thay đổi backend phải có migration/service/test Laravel, cập nhật `docs/openapi.yaml` và Admin API docs.
- Nếu thiếu App Store Connect product IDs, issuer/key, agreement hoặc tài khoản thử nghiệm, hoàn thiện abstraction và test local phần có thể làm, rồi ghi chính xác đầu vào còn thiếu; không tạo secret giả.

Không tuyên bố app “sẵn sàng App Store” chỉ vì build thành công. Phải tách trạng thái kỹ thuật khỏi trạng thái commerce/review.

## 7. Các chức năng và màn hình bắt buộc

### 7.1 Khởi động App

- Launch screen đúng chuẩn iOS, không biến launch screen thành màn hình quảng cáo động.
- Gọi `GET /app-config` để lấy tên shop, logo, banner, màu thương hiệu, liên hệ, phiên bản tối thiểu và trạng thái bảo trì.
- Hiển thị màn hình bảo trì khi backend yêu cầu.
- Hiển thị bắt buộc cập nhật khi `force_update=true` hoặc phiên bản thấp hơn mức tối thiểu, với liên kết App Store được cấu hình hợp lệ.
- Cache cấu hình gần nhất để App có thể khởi động khi mất mạng, nhưng không bỏ qua trạng thái bắt buộc cập nhật đã nhận gần nhất.
- Gọi `GET /home` cho dữ liệu trang chủ.
- Không yêu cầu quyền notification/tracking ngay lúc mở app nếu chưa có ngữ cảnh rõ ràng.

### 7.2 Xác thực và tài khoản

- Đăng ký, đăng nhập, đăng xuất token hiện tại và đăng xuất tất cả thiết bị.
- Quên mật khẩu và đặt lại mật khẩu.
- Gửi lại email xác minh và xử lý liên kết xác minh.
- Xem danh sách phiên đăng nhập và thu hồi từng phiên.
- Đổi mật khẩu, xem và cập nhật hồ sơ.
- Xử lý Universal Links cho reset password/xác minh email; có custom URL scheme fallback nếu hợp lý.
- Ghi rõ phần backend/domain cần cấu hình cho `apple-app-site-association` và Associated Domains. Không tự khai domain giả.
- Sau HTTP 401: xóa session Keychain an toàn và điều hướng về đăng nhập một lần, không tạo vòng lặp gọi API.
- Sau HTTP 422: ánh xạ lỗi theo field lên đúng ô nhập liệu.
- Tài khoản bị khóa phải hiển thị message backend, không đổi thành lỗi mạng chung chung.
- Màn hình cần đăng nhập phải lưu đích ban đầu và quay lại đúng đích sau đăng nhập thành công.

### 7.3 Xóa tài khoản — bắt buộc trước khi gửi App Store

Ứng dụng cho phép tạo tài khoản nên phải cho người dùng khởi tạo xóa tài khoản ngay trong app. Trước tiên kiểm tra lại repository; nếu backend chưa có API phù hợp, bổ sung workflow backend an toàn thay vì chỉ mở trang web chung chung.

- Đặt mục “Xóa tài khoản” dễ tìm trong phần tài khoản/cài đặt.
- Yêu cầu xác nhận rõ hậu quả và re-authentication khi cần; không tạo dark pattern giữ chân người dùng.
- Cho phép workflow xử lý thủ công nếu cần kiểm tra gian lận/pháp lý, nhưng app phải gửi được yêu cầu, hiển thị trạng thái và thời gian dự kiến.
- Thu hồi token/session/device token khi xóa hoàn tất.
- Xóa hoặc ẩn danh dữ liệu cá nhân có thể xóa; không xóa bừa order, wallet ledger hoặc hồ sơ tài chính bắt buộc phải giữ. Cập nhật chính sách bảo mật và giải thích thời gian lưu giữ.
- Gợi ý API có thể dùng sau khi đối chiếu conventions hiện tại: tạo yêu cầu, xem trạng thái và hủy yêu cầu trong thời gian chờ. Tên/method cuối cùng phải nhất quán với backend, không áp đặt endpoint giả nếu đã có giải pháp tốt hơn.
- Thêm authorization, rate limiting, audit log, Laravel feature tests, OpenAPI và Admin docs cho workflow mới.

### 7.4 Trang chủ và catalog

- Banner và các khối sản phẩm nổi bật, bán chạy, mới và khuyến mãi.
- Danh mục và danh sách sản phẩm có phân trang/infinite scroll.
- Tìm kiếm có debounce và hủy task cũ khi query đổi.
- Gợi ý tìm kiếm.
- Bộ lọc: danh mục, khoảng giá, loại sản phẩm, đánh giá, còn hàng.
- Sắp xếp theo API.
- Sản phẩm trending và vừa xem.
- Chi tiết sản phẩm, ảnh, tồn kho, giá, giá gốc, bảo hành, mô tả và đánh giá.
- Hết hàng phải vô hiệu hóa hành động mua.
- Hành động thương mại phải tuân theo `CommerceMode`; không để UI gọi nhầm checkout ngoài mode được phép.

### 7.5 Yêu thích

- Danh sách yêu thích.
- Thêm/xóa từ card sản phẩm và màn hình chi tiết.
- Optimistic UI chỉ dùng khi rollback chính xác khi API lỗi.

### 7.6 Giỏ hàng và voucher

- Xem giỏ hàng, thêm sản phẩm, cập nhật số lượng và xóa sản phẩm.
- Áp dụng và gỡ voucher.
- Luôn dùng tổng tiền backend trả về; tính trên client chỉ để hiển thị tạm, không phải kết quả thanh toán chính thức.
- Xử lý sản phẩm ngừng bán, danh mục bị tắt, giá hoặc tồn kho thay đổi.
- Giỏ/checkout chỉ được bật ở commerce mode hợp lệ.

### 7.7 Checkout và đơn hàng

- Hiển thị số dư ví và tổng thanh toán khi mode phân phối cho phép.
- Checkout bằng `payment_method=wallet` chỉ khi quyết định App Store cho phép luồng đó.
- Với `storeKit`, dùng luồng StoreKit 2 và backend verification thay cho giả lập wallet checkout.
- Chống bấm thanh toán nhiều lần; request giao dịch phải có idempotency phía backend khi cần.
- Danh sách và chi tiết đơn hàng.
- Hiển thị trạng thái thanh toán, trạng thái đơn và bàn giao sản phẩm số.
- Credential được che mặc định, chỉ hiện khi người dùng chủ động; cho sao chép từng trường.
- Không ghi credential, token hoặc mật khẩu vào unified logging, analytics hay crash report.
- Hủy đơn khi backend cho phép, thanh toán lại đơn thất bại và gửi yêu cầu hoàn tiền.
- Với StoreKit, refund/revoke/restore phải đồng bộ đúng với trạng thái server và chính sách Apple.

### 7.8 Ví và nạp tiền

- Số dư ví và lịch sử giao dịch có phân trang.
- Danh sách phương thức nạp đang bật.
- Nạp thẻ: nhà mạng, mệnh giá, serial và mã thẻ; che dữ liệu nhạy cảm.
- Lịch sử nạp thẻ.
- Danh sách tài khoản ngân hàng, tạo hóa đơn chuyển khoản, hiển thị QR/số tài khoản/số tiền/nội dung với nút sao chép.
- Đếm ngược thời hạn hóa đơn.
- Poll trạng thái với khoảng nghỉ hợp lý; dừng khi view không active, app về background hoặc trạng thái kết thúc.
- Hủy hóa đơn đang chờ và xem lịch sử nạp ngân hàng.
- Toàn bộ UI nạp ví/thẻ/chuyển khoản phải bị giới hạn bởi `CommerceMode`; không phát hành chúng trong App Store build nếu không có cơ sở cho phép.

### 7.9 Thông báo

- Danh sách thông báo và badge chưa đọc.
- Đánh dấu từng thông báo hoặc tất cả là đã đọc.
- Điều hướng theo payload, ví dụ mở đúng đơn hàng.
- Đăng ký/cập nhật/xóa device token qua API.
- Tích hợp APNs trực tiếp hoặc Firebase Cloud Messaging cho Apple platforms; chọn một hướng và ghi rõ.
- Xin quyền notification tại thời điểm có ngữ cảnh, xử lý authorized/denied/provisional và deep link khi app cold/warm.
- Nếu thiếu APNs key, provisioning hoặc `GoogleService-Info.plist`, vẫn hoàn thiện protocol, token registration, conditional configuration và hướng dẫn; không commit key giả và không làm debug build thất bại chỉ vì thiếu production secret.

### 7.10 Hỗ trợ khách hàng

- Màn hình chat hỗ trợ.
- Tải tối đa 100 tin gần nhất theo API hiện tại.
- Gửi tin, đánh dấu đã đọc và phân biệt tin khách/Admin.
- Hiển thị trạng thái cuộc trò chuyện đã đóng và vô hiệu hóa gửi khi backend từ chối.
- Không giả lập realtime nếu backend chưa có WebSocket. Có thể refresh có kiểm soát khi màn hình active; dừng task khi rời màn hình hoặc app inactive.

### 7.11 Đánh giá

- Danh sách đánh giá công khai theo sản phẩm và danh sách đánh giá của tôi.
- Tạo đánh giá từ sản phẩm thuộc đơn hoàn tất.
- Sửa/xóa đánh giá của chính người dùng.
- Hiển thị đúng lỗi đã đánh giá hoặc không có quyền.

### 7.12 Nội dung và cài đặt

- FAQ, điều khoản sử dụng, chính sách bảo mật, bảo hành và hoàn tiền.
- Chế độ sáng/tối/hệ thống.
- Thông tin phiên bản app và liên hệ hỗ trợ từ `app-config`.
- Màn hình quản lý/xóa tài khoản.
- Hiển thị acknowledgment/license của dependency nếu cần.

## 8. Navigation đề xuất

Tab bar chính:

1. Trang chủ.
2. Sản phẩm.
3. Giỏ hàng.
4. Đơn hàng.
5. Tài khoản.

Các màn hình con gồm tìm kiếm, chi tiết sản phẩm, checkout, ví, nạp tiền, thông báo, yêu thích, hỗ trợ, đánh giá, phiên đăng nhập, chính sách và cài đặt.

- Giữ state và vị trí scroll hợp lý giữa các tab.
- Dùng `NavigationSplitView` hoặc layout thích ứng trên iPad khi có lợi.
- Deep link phải được parse type-safe, từ chối path/ID không hợp lệ và không bỏ qua kiểm tra auth/ownership.
- Tab/hành động thương mại thay đổi đúng theo `CommerceMode` mà không tạo dead-end.

## 9. Thiết kế giao diện và accessibility

- Dựa trên nhận diện ShopKey hiện có: nền tối gần đen, màu nhấn đỏ, card rõ ràng; đồng thời hỗ trợ light appearance.
- Không nhúng website vào `WKWebView` để thay thế app native. Web view chỉ dùng cho nội dung/pháp lý phù hợp và phải an toàn.
- Tạo design tokens cho color, typography, spacing, corner radius và elevation/shadow.
- Hỗ trợ Dynamic Type, VoiceOver, Bold Text, Reduce Motion, contrast, focus order và touch target hợp lý.
- Không truyền đạt trạng thái chỉ bằng màu; icon trang trí phải ẩn khỏi accessibility tree.
- Chuỗi hiển thị dùng String Catalog hoặc localization resources; mặc định tiếng Việt và chuẩn bị tiếng Anh.
- Định dạng VND bằng `FormatStyle`/locale Việt Nam.
- Parse ISO-8601 an toàn, bao gồm fractional seconds khi API có thể trả về, và hiển thị theo múi giờ thiết bị.
- Tôn trọng safe area, bàn phím, kích thước màn hình nhỏ, landscape và iPad multitasking.
- Không tự ý xin Photos/Camera/Tracking/Contacts nếu chức năng không cần.

## 10. Network, cache và xử lý lỗi

- Một API client trung tâm thêm `Accept: application/json` và Bearer token khi có.
- Chỉ thêm `Content-Type` khi request có body phù hợp.
- Timeout hợp lý; không tự retry POST tạo giao dịch như checkout/nạp tiền.
- Chỉ retry có kiểm soát cho GET hoặc request thật sự idempotent, có backoff và tôn trọng 429/`Retry-After`.
- Parse HTTP 401, 403, 404, 422, 429 và 5xx thành lỗi domain rõ ràng.
- Tôn trọng `message` và `errors` từ backend.
- Phân biệt offline, timeout, TLS, cancelled task, decoding và server error; không coi mọi `URLError` là mất mạng.
- Cache catalog/home theo stale-while-revalidate hợp lý.
- Không cache credential bàn giao, mã thẻ, serial hoặc dữ liệu thanh toán nhạy cảm trong SwiftData/Core Data/URLCache.
- Debug logging phải redact `Authorization`, password, token, serial, code, credential và URL/query nhạy cảm.
- Release tắt body logging và verbose logging.
- Khi task bị hủy do view lifecycle, không hiển thị lỗi giả cho người dùng.

## 11. Bảo mật và quyền riêng tư

- Lưu token trong Keychain với accessibility phù hợp, ưu tiên biến thể `ThisDeviceOnly` khi không cần đồng bộ iCloud.
- Không lưu mật khẩu người dùng.
- Không hardcode secret hoặc private API key.
- Release bắt buộc HTTPS và ATS nghiêm ngặt.
- Không đưa secret/credential/PII vào analytics hoặc crash report.
- Không tự triển khai certificate pinning cứng nếu chưa có chiến lược xoay chứng chỉ.
- Credential sản phẩm được che mặc định. Đánh giá việc hạn chế chụp/ghi màn hình theo API công khai iOS và UX thực tế; không tuyên bố iOS có cơ chế tương đương tuyệt đối với Android `FLAG_SECURE`.
- Theo dõi trạng thái screen capture nếu dùng và che nội dung nhạy cảm hợp lý, nhưng không dựa duy nhất vào biện pháp client.
- Có `PrivacyInfo.xcprivacy` chính xác cho app và kiểm tra manifest/privacy reason của SDK bên thứ ba.
- `Info.plist` chỉ khai usage description cho quyền thực sự sử dụng; mô tả rõ mục đích.
- Chuẩn bị nội dung App Privacy trong App Store Connect dựa trên dữ liệu thực tế; không đoán hoặc khai thiếu.
- Mọi thao tác cập nhật/xóa dựa vào kết quả server và authorization server-side, không tin ID client tự suy luận.

## 12. App Store submission readiness

Chuẩn bị tài liệu kỹ thuật trong `ios-app/README.md` hoặc thư mục `ios-app/docs/`:

- Bundle ID, version/build number strategy và minimum iOS.
- Cách cấu hình signing, capabilities, Associated Domains và push notifications.
- Commerce decision và StoreKit/external-purchase prerequisites.
- Privacy manifest, privacy policy URL, support URL và account-deletion flow.
- Danh sách URL/domain production dùng bởi app.
- Hướng dẫn tạo archive nhưng không commit signing key/profile.
- Checklist ảnh chụp, metadata, age rating và review notes.
- Review notes phải giải thích rõ luồng đăng nhập, commerce mode, dữ liệu số được giao và cách reviewer test.
- Cung cấp demo account hoặc demo mode đầy đủ nếu app cần đăng nhập; backend review phải hoạt động và dữ liệu test không hết hạn trong thời gian review.
- Không quảng cáo app là được Apple phê duyệt khi chưa thực sự được duyệt.

## 13. Testing bắt buộc

Tạo và chạy:

- Unit test cho repository, use case, mapper, money/date formatter và validation client.
- Test state model/ViewModel cho loading/success/empty/error và cancellation.
- Contract test bằng custom `URLProtocol` hoặc mock server cho envelope thành công, 401, 422, 429 và malformed JSON.
- Test token injection và log redaction.
- Test pagination, duplicate-page protection và refresh.
- Test checkout chống double tap/idempotency.
- Test polling hóa đơn dừng đúng khi view biến mất hoặc scene về background.
- Test Keychain wrapper bằng abstraction/fake; không dựa vào Keychain production trong unit test.
- XCUITest hoặc SwiftUI integration test cho đăng nhập, catalog, giỏ hàng, checkout, chi tiết đơn và nạp ngân hàng ở mode được phép.
- Ít nhất một smoke flow dùng fake server: đăng nhập → xem sản phẩm → thêm giỏ → checkout → xem đơn.
- Nếu dùng StoreKit, test purchase success, pending, user-cancelled, unverified, restore, refund/revoke bằng StoreKit Test.
- Test dark/light appearance, Dynamic Type lớn và ít nhất iPhone/iPad destination phù hợp khi môi trường có simulator.

Trước khi bàn giao, trên macOS có Xcode phù hợp phải chạy tối thiểu các lệnh tương đương:

```bash
xcodebuild -project ios-app/ShopKey.xcodeproj \
  -scheme ShopKey \
  -sdk iphonesimulator \
  -configuration Debug \
  CODE_SIGNING_ALLOWED=NO build

xcrun simctl list devices available

xcodebuild -project ios-app/ShopKey.xcodeproj \
  -scheme ShopKey \
  -configuration Debug \
  -destination 'platform=iOS Simulator,name=<SIMULATOR_AVAILABLE>' \
  test
```

Tên project/scheme có thể điều chỉnh nếu cấu trúc thực tế khác, nhưng phải ghi lệnh chính xác đã chạy.

Nếu Codex đang chạy trên Windows/Linux hoặc máy không có Xcode:

- Phải nói rõ `xcodebuild` chỉ chạy trên macOS với Xcode.
- Vẫn hoàn thiện source, project/config, tests và kiểm tra tĩnh mọi phần có thể kiểm tra trong môi trường hiện tại.
- Ghi lệnh macOS chính xác cần chạy tiếp và không tuyên bố build/test iOS đã đạt khi chưa chạy thật.
- Không dùng compiler Swift server-side thay thế để tuyên bố SwiftUI app build thành công.

Sau mọi thay đổi backend, chạy lại test Laravel hiện có và xác nhận không regression.

## 14. Tiêu chí nghiệm thu

Chỉ coi phần kỹ thuật iOS hoàn tất khi:

- Dự án trong `ios-app/` mở, resolve package và build được bằng phiên bản Xcode đã ghi rõ.
- Shared scheme tồn tại và `xcodebuild` Debug cho simulator thành công.
- Unit/integration tests đã chạy thành công; UI test nào chưa chạy phải được liệt kê trung thực.
- Không còn compile error, crash đã biết hoặc warning nghiêm trọng do code dự án.
- Toàn bộ chức năng bắt buộc có màn hình và nối API thật, không phải TODO/mock cố định.
- Loading, empty, error, offline và retry hoạt động đúng.
- Đăng nhập được lưu an toàn và khôi phục khi mở lại app.
- 401 đưa về đăng nhập đúng cách; 422 hiển thị đúng lỗi field.
- Catalog, yêu thích, giỏ, đơn hàng, credential, ví, thông báo, hỗ trợ và đánh giá hoạt động theo backend trong commerce mode tương ứng.
- Token/credential/mã thẻ không xuất hiện trong log hoặc storage không phù hợp.
- Dark/light, Dynamic Type, VoiceOver cơ bản và iPad layout hoạt động.
- Account deletion hoạt động end-to-end và có tài liệu retention.
- `APP_STORE_COMMERCE_DECISION.md` tồn tại, có bằng chứng và không mặc định cho phép thanh toán ngoài.
- Nếu dùng StoreKit, server verification và transaction lifecycle đã được kiểm thử; không giao hàng chỉ vì client báo success.
- Có README hướng dẫn base URL, Xcode, simulator, signing, push, Universal Links, StoreKit, build/test/archive và external inputs.
- Có danh sách rõ ràng thứ chủ dự án phải cung cấp: production domain, Apple Developer Team, Bundle ID, signing/profile, App Store Connect app/product IDs, StoreKit/App Store Server credentials nếu dùng, APNs key hoặc Firebase config, Associated Domains và tài khoản review.
- Không tự tạo hoặc commit secret production.

Trạng thái “build/test thành công” và “đủ điều kiện gửi App Store” là hai tiêu chí riêng. Chỉ tuyên bố mục thứ hai khi commerce, account deletion, privacy, signing và review assets đều đã được xác minh.

## 15. Quy trình làm việc yêu cầu đối với Codex

1. Khảo sát repository và đọc toàn bộ chỉ dẫn liên quan trước khi sửa.
2. Kiểm tra `ios-app/` đã tồn tại chưa; nếu có, giữ thay đổi hợp lệ và phát triển tiếp, không xóa bừa.
3. Kiểm tra OS, Xcode, Swift, simulator và công cụ signing hiện có; báo cáo giới hạn môi trường trung thực.
4. Đối chiếu OpenAPI/routes/resources/requests và lập inventory endpoint theo feature.
5. Đọc App Review Guidelines hiện hành và hoàn thành commerce decision trước khi bật checkout/nạp tiền trong Release.
6. Đưa ra kế hoạch ngắn theo giai đoạn rồi bắt đầu ngay: nền tảng → auth → catalog → cart/commerce → orders/wallet → các feature còn lại → hardening/submission.
7. Ưu tiên vertical slice chạy được, build/test thường xuyên, không dồn lỗi tới cuối.
8. Không sửa backend chỉ để né parse đúng API.
9. Khi phát hiện lỗi/gap backend có bằng chứng, sửa tối thiểu, thêm test Laravel và cập nhật OpenAPI/Admin docs. Account deletion và StoreKit server verification là các gap cần xử lý nếu flow tương ứng chưa tồn tại.
10. Không dừng ở scaffold. Tiếp tục cho tới khi mọi tiêu chí có thể kiểm tra trong môi trường đều đạt.
11. Nếu bị chặn bởi macOS/Xcode, Apple Developer account, Firebase/APNs, signing, production domain hoặc App Store credential, hoàn thiện toàn bộ phần không bị chặn và ghi chính xác đầu vào còn thiếu.
12. Không tự publish, tạo certificate, gửi App Store review hoặc thực hiện giao dịch thật nếu chưa được chủ dự án cho phép rõ ràng.
13. Khi bàn giao, báo cáo ngắn gọn:
    - Chức năng đã hoàn thành.
    - Kiến trúc và quyết định commerce.
    - File quan trọng.
    - Test/build đã chạy, command và kết quả thực tế.
    - Thay đổi backend nếu có và kết quả test Laravel.
    - Cấu hình/dịch vụ ngoài còn cần.
    - Phần chưa kiểm chứng; không tuyên bố hoàn tất thay cho bằng chứng.

Hãy bắt đầu bằng việc kiểm tra `docs/openapi.yaml`, `routes/api.php`, cấu trúc repository, trạng thái `ios-app/`, môi trường macOS/Xcode/Swift hiện có và quy định App Store hiện hành. Sau đó lập inventory endpoint, viết commerce decision ban đầu và triển khai ứng dụng theo specification này.
