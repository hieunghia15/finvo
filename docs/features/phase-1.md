# Phase 1 — Chi tiết chức năng

## 1. Dashboard

### 1.1. Xem tổng thu nhập

- Tính tổng các khoản thu trong khoảng thời gian được chọn.
- Có thể tính theo:
    - toàn bộ ví
    - hoặc từng ví nếu sau này cần mở rộng.
- Chỉ tính các giao dịch hợp lệ/chưa bị xóa.
- Dữ liệu lấy từ các giao dịch có loại income.
- Liên quan ERD:
    - `transactions → wallets, categories`

### 1.2. Xem tổng chi tiêu

- Tính tổng các khoản chi trong khoảng thời gian.
- Chỉ tính giao dịch có loại expense.
- Liên quan ERD:
    - `transactions → wallets, categories`

### 1.3. Xem tổng số dư hiện tại

- Có thể xác định theo:
    ```text
    Tổng số dư = Tổng số dư ban đầu của các ví
               + Tổng thu
               - Tổng chi
    ```
- Liên quan ERD:
    - `wallets + transactions`

### 1.4. Xem 10 giao dịch gần đây

- Hiển thị tối đa 10 giao dịch mới nhất.
- Sắp xếp theo:
    - ngày giao dịch
    - hoặc `created_at`.
- Hiển thị các thông tin cơ bản:
    - danh mục
    - ví
    - loại thu/chi
    - số tiền
    - ngày
    - ghi chú.

### 1.5. Lọc thống kê theo khoảng thời gian

- Các lựa chọn:
    - Hôm nay
    - Một ngày cụ thể
    - Tuần này
    - Tháng này
    - Khoảng ngày tùy chọn
- Có thể dùng chung logic filter với màn hình Transactions.
- Điểm quan trọng cho ERD:
    - Dashboard không cần bảng riêng. Đây chủ yếu là dữ liệu tổng hợp từ `transactions` và `wallets`.

---

## 2. Transactions / Giao dịch

Đây là entity trung tâm của Phase 1.

### 2.1. Xem danh sách giao dịch

- Mỗi giao dịch có thể hiển thị:
    - ID
    - Loại giao dịch: thu / chi
    - Số tiền
    - Ngày giao dịch
    - Danh mục
    - Ví
    - Ghi chú
    - Thời gian tạo
- Một giao dịch thuộc:
    - 1 account/user
    - 1 wallet
    - 1 category

### 2.2. Thêm giao dịch

- Người dùng nhập:
    - Loại giao dịch: Thu / Chi
    - Số tiền
    - Ngày giao dịch
    - Danh mục
    - Ví tiền
    - Ghi chú
- **Business rule**
    - Số tiền > 0.
    - Ví phải tồn tại.
    - Danh mục phải tồn tại.
    - Danh mục phải phù hợp với loại giao dịch.
        - Ví dụ: Ăn uống → không thể chọn cho Thu.
    - Ví phải thuộc tài khoản hiện tại.
    - Ngày giao dịch hợp lệ.
- **Khi tạo giao dịch**
    - Có thể ảnh hưởng đến:
        - số dư ví
        - tổng thu
        - tổng chi
        - dashboard.

### 2.3. Sửa giao dịch

- Có thể sửa:
    - loại giao dịch
    - số tiền
    - ngày
    - danh mục
    - ví
    - ghi chú.
- **Đặc biệt quan trọng**
    - Nếu thay đổi:
        - `100.000 Chi` → `200.000 Chi`
        - thì số dư ví phải được tính lại chính xác.
    - Nếu đổi:
        - `Ví A` → `Ví B`
        - thì: hoàn lại ảnh hưởng lên Ví A, áp dụng ảnh hưởng lên Ví B.
    - Nếu đổi:
        - `Chi` → `Thu`
        - thì số dư cũng phải thay đổi tương ứng.
    - Đây là lý do nên xem transactions là nguồn dữ liệu nghiệp vụ, thay vì lưu `current_balance` rồi cập nhật một cách độc lập mà không kiểm soát.

### 2.4. Xóa giao dịch

- Xóa một giao dịch.
- Sau khi xóa:
    - số dư ví phải được cập nhật lại
    - dashboard phải thay đổi.
- Có thể dùng:
    - hard delete
    - hoặc soft delete.
- Đối với project cá nhân, soft delete có thể là hướng mở rộng sau này; Phase 1 có thể dùng hard delete để đơn giản.

### 2.5. Xem chi tiết giao dịch

- Hiển thị đầy đủ:
    - Loại
    - Số tiền
    - Danh mục
    - Ví
    - Ngày giao dịch
    - Ghi chú
    - Ngày tạo
    - Ngày cập nhật
- Có thể hiển thị thêm:
    - Người tạo
    - Trạng thái

### 2.6. Tìm kiếm giao dịch

- Tìm theo:
    - ghi chú
    - tên danh mục
    - tên ví
- Ví dụ:
    - "cafe"
    - "lương"
    - "tiền nhà"
- Về ERD, việc tìm theo category/wallet là lý do quan hệ giữa các bảng cần rõ ràng.

### 2.7. Lọc theo khoảng thời gian

- Một ngày
- Từ ngày → ngày
- Tuần
- Tháng
- Ví dụ: `01/09/2026` → `18/09/2026`

### 2.8. Lọc theo danh mục

- Ví dụ:
    - Ăn uống
    - Di chuyển
    - Mua sắm
    - Lương
    - Thưởng
- Một category có thể có nhiều transaction.
    - `Category 1 ──── N Transactions`

### 2.9. Lọc theo loại giao dịch

- Hai loại chính:
    - income
    - expense
- Có thể lưu dạng: `type`

### 2.10. Lọc theo khoảng tiền

- Ví dụ:
    - Từ: 100.000
    - Đến: 500.000
- Có thể hỗ trợ:
    - `=` số tiền
    - `<=` số tiền
    - khoảng tiền.

### 2.11. Phân trang danh sách giao dịch

- Ví dụ: 20 giao dịch / trang
- Không ảnh hưởng nhiều đến ERD nhưng ảnh hưởng query/index.

---

## 3. Categories / Danh mục

### 3.1. Xem danh sách danh mục

- Hiển thị:
    - tên danh mục
    - loại: thu / chi
    - số lượng giao dịch sử dụng danh mục.

### 3.2. Thêm danh mục

- Thông tin:
    - Tên danh mục
    - Loại danh mục: income / expense
- Ví dụ:
    - Lương → income
    - Thưởng → income
    - Ăn uống → expense
    - Di chuyển → expense
    - Mua sắm → expense
- **Business rule**
    - Có thể giới hạn:
        - Một account không được có 2 category cùng tên và cùng loại
    - Điều này sẽ ảnh hưởng tới unique constraint trong DB.

### 3.3. Sửa danh mục

- Có thể sửa:
    - tên
    - loại
- **Cần lưu ý**
    - Nếu category đã có transaction:
        - `Ăn uống` → `expense`
    - thì không nên cho đổi tùy tiện thành:
        - `Ăn uống` → `income`
    - hoặc phải có business rule xử lý.
    - Để Phase 1 đơn giản, có thể quy định: Không cho thay đổi type nếu category đã được sử dụng.

### 3.4. Xóa danh mục

- Trước khi xóa cần kiểm tra:
    - category có transaction hay không.
- => Không cho xóa nếu đang được sử dụng.

### 3.5. Phân loại danh mục theo thu/chi

- Mỗi category có: `type = income | expense`
- Như vậy:
    ```text
    Account
       │
       ├── Category (income)
       │
       └── Category (expense)
    ```

---

## 4. Wallets / Ví tiền

Đây là phần tôi khuyên nên thiết kế rõ ngay từ Phase 1 vì nó ảnh hưởng trực tiếp đến ERD.

### 4.1. Xem danh sách ví tiền

- Hiển thị:
    - tên ví
    - số dư hiện tại
    - số dư ban đầu
    - trạng thái nếu có
    - ngày tạo.
- Ví dụ:
    - Tiền mặt 2.000.000
    - Ngân hàng 8.500.000
    - MoMo 1.200.000

### 4.2. Thêm ví tiền

- Thông tin:
    - Tên ví
    - Số dư ban đầu
    - Mô tả nếu cần
- Ví dụ:
    - Tên: Tiền mặt
    - Số dư ban đầu: 1.000.000
- Quan hệ
    - `Account 1 ──── N Wallets`
    - Một account có nhiều ví.

### 4.3. Sửa ví tiền

- Có thể sửa:
    - tên ví
    - mô tả
- Cần cân nhắc việc cho sửa: `initial_balance`
- Tôi khuyên không cho sửa trực tiếp sau khi ví đã có giao dịch, vì sẽ làm lịch sử số dư khó kiểm soát.

### 4.4. Xóa ví tiền

- Trước khi xóa cần kiểm tra: ví có transaction hay không.
- Ví dụ:
    ```text
    Ví A
      └── 25 transactions
    ```
    thì không nên xóa trực tiếp.
- Phase 1 có thể: Không cho xóa ví đã phát sinh giao dịch.

### 4.5. Xem số dư hiện tại của ví

- Công thức:
    ```text
    Current Balance = Initial Balance + Total Income - Total Expense
    ```
- Ví dụ:
    ```text
    Initial balance = 1.000.000
    Income          = 3.000.000
    Expense         = 1.500.000
    Current balance = 2.500.000
    ```
- **Điểm ERD quan trọng**
    - Hướng A — Không lưu `current_balance`
        - `wallet.initial_balance` + `transactions` rồi tính toán.
    - Đối với project của bạn, tôi thiên về A trong Phase 1 để tránh dữ liệu số dư bị lệch giữa wallets và transactions.

### 4.6. Thiết lập số dư ban đầu

- Khi tạo ví: `initial_balance`
- Ví dụ:
    - Tiền mặt: Initial balance = 2.000.000
- Đây là dữ liệu thuộc Wallet, không phải Transaction.

### 4.7. Chọn ví khi thêm giao dịch

- Khi tạo transaction:
    ```text
    Transaction
       │
       └── wallet_id
    ```
- Ví dụ:
    - Chi 100.000
    - Category = Ăn uống
    - Wallet = Tiền mặt

### 4.8. Lọc giao dịch theo ví

- Ví dụ: Wallet = Tiền mặt → chỉ lấy transaction của wallet đó.
- Quan hệ: `Wallet 1 ──── N Transactions`

### 4.9. Xem lịch sử giao dịch của từng ví

- Từ: Wallet detail
- có thể xem:
    - Tên ví
    - Số dư hiện tại
- Transaction history:
    - `+5.000.000` Lương
    - `-500.000` Tiền nhà
    - `-100.000` Ăn uống
- Đây thực chất là:
    ```text
    Wallet
       ↓
    Transactions
    ```
    với filter `wallet_id`.

---

## 5. Accounts / Tài khoản người dùng

Ở đây nên phân biệt rõ `Account/User` với `Wallet`. Một account có thể sở hữu nhiều wallet.

### 5.1. Đăng ký tài khoản

- Thông tin tối thiểu:
    - email
    - password
    - tên hiển thị nếu cần
- Có thể thêm: `created_at`, `updated_at`
- Quan hệ chính:
    - `Account 1 ──── N Wallets`
    - `Account 1 ──── N Categories`
    - `Account 1 ──── N Transactions`

### 5.2. Xem thông tin tài khoản

- Hiển thị:
    - tên
    - email
    - ngày đăng ký
- Không hiển thị password.

### 5.3. Cập nhật thông tin tài khoản

- Có thể cập nhật: tên

### 5.4. Đổi mật khẩu

- Thông tin:
    - mật khẩu hiện tại
    - mật khẩu mới
    - xác nhận mật khẩu mới.
- Không lưu plaintext password.
- DB lưu: `password_hash`
