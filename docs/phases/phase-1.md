# Phase 1 — Implementation Plan

> Nguồn: [`features/phase-1.md`](../features/phase-1.md) + các quyết định đã chốt trong buổi review (Q1–Q40).
> Mục tiêu chính của tài liệu: làm cơ sở để tạo **migration** và các thành phần liên quan (Enum, Model, Seeder) cho Phase 1, sau đó triển khai từng chức năng.

---

## 0. Phạm vi

### Trong phạm vi Phase 1

- Dashboard (tổng thu, tổng chi, tổng số dư, 10 giao dịch gần đây, lọc thời gian).
- Transactions: CRUD, xem chi tiết, tìm kiếm, lọc, sắp xếp, phân trang.
- Categories: CRUD, phân loại thu/chi, trạng thái.
- Wallets: CRUD, loại ví, currency, số dư ban đầu, trạng thái, lịch sử giao dịch theo ví.
- Accounts: đăng ký, xem thông tin, cập nhật tên, đổi mật khẩu.
- Đa tiền tệ ở mức **hiển thị/nhóm theo currency** (không quy đổi).

### Ngoài phạm vi (không implement, không tạo cột/bảng)

- Chuyển tiền giữa các ví (transfer). User tự ghi 1 giao dịch chi + 1 giao dịch thu.
- Quy đổi tỷ giá, bảng `exchange_rates`.
- Soft delete cho bất kỳ bảng nào.
- Đổi email, xác thực email bắt buộc, quên mật khẩu, xóa tài khoản.
- Icon/color cho danh mục; `sort_order` cho ví/danh mục; cột `is_default` cho ví.
- Trạng thái (`status`) của giao dịch; hiển thị "Người tạo".
- UI quản lý currency.

---

## 1. Quy ước kỹ thuật chung

| Chủ đề                  | Quyết định                                                                                                                                                                    |
| ----------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Database                | MySQL (≥ 8.0.16 để CHECK constraint được enforce) cho cả dev **và test** (DB test riêng, không dùng SQLite).                                                                  |
| Collation               | `utf8mb4_unicode_ci` (mặc định hiện tại). Hệ quả chấp nhận: so sánh không phân biệt hoa/thường **và dấu** — "Lương" = "Luong" = "LƯƠNG" trong unique constraint và tìm kiếm.  |
| Timezone                | Đổi `config/app.php` → `'timezone' => 'Asia/Ho_Chi_Minh'`. Không có timezone theo user.                                                                                       |
| Kiểu tiền               | `DECIMAL(19,4)` cho mọi cột tiền. Không dùng `UNSIGNED` (deprecated với DECIMAL trong MySQL 8). Ràng buộc dấu bằng validation + CHECK constraint.                             |
| Truyền tiền ra frontend | Eloquent cast `decimal:4` → **string**. Inertia props truyền string, frontend không parse sang JS `number` để tính toán. Mọi phép cộng tổng thực hiện bằng `SUM()` trong SQL. |
| Định dạng hiển thị tiền | Frontend dùng `Intl.NumberFormat` theo `currency.code` + `decimal_places`. Ví dụ `-100.000 ₫`, `-12,50 $`.                                                                    |
| Enum                    | Lưu `VARCHAR` + PHP backed enum + Eloquent cast. Không dùng MySQL `ENUM`.                                                                                                     |
| Khóa chính              | ID tự tăng (`bigint`) cho bảng nghiệp vụ; `currencies` dùng `code CHAR(3)` làm PK. Không dùng UUID/ULID.                                                                      |
| Phân quyền              | Mọi truy vấn scope theo user hiện tại (`$request->user()->wallets()->findOrFail($id)` hoặc Policy). Truy cập bản ghi của user khác → **404**.                                 |
| Chuẩn hóa chuỗi         | `name` (ví, danh mục) được trim + gộp khoảng trắng liên tiếp trong `prepareForValidation` của FormRequest.                                                                    |

---

## 2. PHP Enums (`app/Enums/`)

| Enum              | Giá trị                                            | Dùng cho                               |
| ----------------- | -------------------------------------------------- | -------------------------------------- |
| `TransactionType` | `income`, `expense`                                | `transactions.type`, `categories.type` |
| `EntityStatus`    | `active`, `inactive`, `archived`                   | `wallets.status`, `categories.status`  |
| `WalletType`      | `cash`, `bank`, `e_wallet`, `credit_card`, `other` | `wallets.type`                         |

> Tên enum có thể điều chỉnh khi implement, nhưng giá trị lưu DB phải giữ đúng như bảng trên.

---

## 3. Database Schema & Migrations

Thứ tự migration (do phụ thuộc FK):

1. `create_currencies_table`
2. `create_wallets_table`
3. `create_categories_table`
4. `create_transactions_table`

Bảng `users` **giữ nguyên** (cột `password` là hash qua cast `hashed`; mục 5.4 "password_hash" được hiểu là "lưu dạng hash", không đổi tên cột).

### 3.1. `currencies`

| Cột                        | Kiểu               | Ràng buộc                                              |
| -------------------------- | ------------------ | ------------------------------------------------------ |
| `code`                     | `CHAR(3)`          | **PK**, mã ISO 4217 (VND, USD…)                        |
| `name`                     | `VARCHAR(100)`     | not null                                               |
| `symbol`                   | `VARCHAR(10)`      | not null                                               |
| `decimal_places`           | `TINYINT UNSIGNED` | not null, 0–4 (CHECK `decimal_places BETWEEN 0 AND 4`) |
| `is_active`                | `BOOLEAN`          | default `true`                                         |
| `created_at`, `updated_at` | timestamps         |                                                        |

**Seed (`CurrencySeeder`, gọi trong `DatabaseSeeder`, dùng `upsert` theo `code` để chạy lại an toàn):**

| code | name              | symbol | decimal_places |
| ---- | ----------------- | ------ | -------------- |
| VND  | Việt Nam Đồng     | ₫      | 0              |
| USD  | US Dollar         | $      | 2              |
| EUR  | Euro              | €      | 2              |
| JPY  | Japanese Yen      | ¥      | 0              |
| GBP  | British Pound     | £      | 2              |
| CNY  | Chinese Yuan      | ¥      | 2              |
| KRW  | South Korean Won  | ₩      | 0              |
| SGD  | Singapore Dollar  | S$     | 2              |
| THB  | Thai Baht         | ฿      | 2              |
| AUD  | Australian Dollar | A$     | 2              |

> Deploy: bắt buộc chạy seeder currency ở mọi môi trường (kể cả production), vì đăng ký tài khoản cần currency `VND` tồn tại. `php artisan db:seed --force` ở production chỉ tạo currency: tài khoản admin (`AdminUserSeeder`) không được seed ở production, người dùng tự đăng ký qua `/register`.

### 3.2. `wallets`

| Cột                        | Kiểu              | Ràng buộc                                      |
| -------------------------- | ----------------- | ---------------------------------------------- |
| `id`                       | `BIGINT UNSIGNED` | PK                                             |
| `user_id`                  | `BIGINT UNSIGNED` | FK → `users.id`, **ON DELETE CASCADE**         |
| `name`                     | `VARCHAR(100)`    | not null                                       |
| `type`                     | `VARCHAR(20)`     | default `'other'` (`WalletType`)               |
| `currency_code`            | `CHAR(3)`         | FK → `currencies.code`, **ON DELETE RESTRICT** |
| `initial_balance`          | `DECIMAL(19,4)`   | default `0`, CHECK `initial_balance >= 0`      |
| `description`              | `VARCHAR(255)`    | nullable                                       |
| `status`                   | `VARCHAR(20)`     | default `'active'` (`EntityStatus`)            |
| `created_at`, `updated_at` | timestamps        |                                                |

**Index / Unique**

- `UNIQUE (user_id, name)`: tên ví unique theo user, áp dụng cho **mọi trạng thái**.
- `INDEX (user_id, status)`

**Không có** cột `current_balance`. Số dư được tính lúc đọc (xem §4.1).

### 3.3. `categories`

| Cột                        | Kiểu              | Ràng buộc                                |
| -------------------------- | ----------------- | ---------------------------------------- |
| `id`                       | `BIGINT UNSIGNED` | PK                                       |
| `user_id`                  | `BIGINT UNSIGNED` | FK → `users.id`, **ON DELETE CASCADE**   |
| `name`                     | `VARCHAR(100)`    | not null                                 |
| `type`                     | `VARCHAR(10)`     | `TransactionType` (`income` / `expense`) |
| `status`                   | `VARCHAR(20)`     | default `'active'` (`EntityStatus`)      |
| `created_at`, `updated_at` | timestamps        |                                          |

**Index / Unique**

- `UNIQUE (user_id, name, type)`: áp dụng cho **mọi trạng thái** (kể cả archived). Muốn dùng lại tên của danh mục đã archived thì phải khôi phục danh mục đó.
- `INDEX (user_id, type, status)`

### 3.4. `transactions`

| Cột                        | Kiểu              | Ràng buộc                                                                               |
| -------------------------- | ----------------- | --------------------------------------------------------------------------------------- |
| `id`                       | `BIGINT UNSIGNED` | PK                                                                                      |
| `user_id`                  | `BIGINT UNSIGNED` | FK → `users.id`, **ON DELETE CASCADE** (denormalized, phải khớp chủ ví và chủ danh mục) |
| `wallet_id`                | `BIGINT UNSIGNED` | FK → `wallets.id`, **ON DELETE RESTRICT**                                               |
| `category_id`              | `BIGINT UNSIGNED` | FK → `categories.id`, **ON DELETE RESTRICT**                                            |
| `type`                     | `VARCHAR(10)`     | `TransactionType`                                                                       |
| `amount`                   | `DECIMAL(19,4)`   | not null, CHECK `amount > 0`                                                            |
| `transaction_date`         | `DATE`            | not null                                                                                |
| `note`                     | `VARCHAR(255)`    | nullable                                                                                |
| `created_at`, `updated_at` | timestamps        |                                                                                         |

**Index**

- `INDEX (user_id, transaction_date, id)`: dashboard, danh sách, 10 giao dịch gần nhất.
- `INDEX (wallet_id, transaction_date)`: lịch sử theo ví, tính số dư ví.
- `INDEX (category_id)`: lọc theo danh mục, đếm giao dịch của danh mục, kiểm tra trước khi xóa.

**Xóa:** hard delete.

> Lưu ý khi viết migration: Laravel Blueprint không có API cho CHECK constraint. Thêm bằng `DB::statement('ALTER TABLE ... ADD CONSTRAINT ... CHECK (...)')` trong `up()`.

### 3.5. Sơ đồ quan hệ

```text
users 1 ──── N wallets ──── N transactions
  │                              │
  ├──── N categories ──── N ─────┘
  └──── N transactions (user_id denormalized)

currencies 1 ──── N wallets (currency_code)
```

---

## 4. Business Rules

### 4.1. Số dư

```text
Wallet current balance = initial_balance
                       + SUM(amount WHERE type = income)
                       - SUM(amount WHERE type = expense)
```

- Tính bằng subquery/aggregate SQL, không lưu vào DB.
- **Ví được phép âm.** Không chặn giao dịch chi vượt số dư; frontend chỉ hiển thị cảnh báo khi số dư dự kiến < 0.
- Sửa/xóa giao dịch không cần xử lý "hoàn tác số dư" vì số dư luôn được tính lại từ `transactions`. Các case đổi số tiền, đổi ví A → B, đổi Chi ↔ Thu (mục 2.3) đều đúng tự động.

### 4.2. Ma trận trạng thái (áp dụng cho **cả ví và danh mục**)

| Hành vi                                     | active          | inactive   | archived                                  |
| ------------------------------------------- | --------------- | ---------- | ----------------------------------------- |
| Hiện trong danh sách mặc định               | ✅              | ✅ (badge) | ❌ (chỉ hiện khi bật filter "Đã lưu trữ") |
| Chọn được khi **tạo** giao dịch mới         | ✅              | ❌         | ❌                                        |
| Giao dịch cũ vẫn hiển thị, sửa/xóa được     | ✅              | ✅         | ✅                                        |
| Được **chuyển** giao dịch khác sang khi sửa | ✅              | ❌         | ❌                                        |
| Tính vào số dư / thống kê dashboard         | ✅              | ✅         | ✅                                        |
| Chuyển trạng thái                           | tự do mọi chiều | tự do      | tự do (khôi phục được)                    |
| Sửa thông tin (tên, mô tả…)                 | ✅              | ✅         | ❌ (phải khôi phục trước)                 |
| Xóa (khi **chưa có** giao dịch)             | ✅              | ✅         | ✅                                        |

### 4.3. Wallets

- Tên unique theo user (không phân biệt hoa/thường/dấu do collation).
- `type`: sửa được bất cứ lúc nào.
- `currency_code`: chọn được khi tạo, **chỉ được đổi khi ví chưa có giao dịch nào**. Chỉ được chọn currency có `is_active = true`.
- `initial_balance`: `>= 0`, **chỉ được sửa khi ví chưa có giao dịch nào**.
- Số chữ số thập phân của `initial_balance` ≤ `currency.decimal_places`.
- Xóa: chỉ khi ví chưa có giao dịch (DB `RESTRICT` là lớp bảo vệ cuối cùng).
- Sắp xếp danh sách: `created_at ASC`.

### 4.4. Categories

- Unique `(user_id, name, type)`.
- `type`: **chỉ được đổi khi danh mục chưa có giao dịch**.
- Xóa: chỉ khi chưa có giao dịch.
- Danh sách hiển thị số giao dịch (`withCount`), đếm cả danh mục archived.
- Sắp xếp: `id DESC` (mới nhất trước), giống nhau ở danh sách đầy đủ lẫn khi lọc theo `type`.

### 4.5. Transactions: validation khi tạo/sửa

| Trường             | Rule                                                                                                                                                         |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `type`             | required, `income` \| `expense`                                                                                                                              |
| `amount`           | required, `> 0`, `<= 999.999.999.999`, số chữ số thập phân ≤ `decimal_places` của currency **của ví được chọn** (VND/JPY/KRW: số nguyên; USD/EUR…: tối đa 2) |
| `transaction_date` | required, date, `>= 2000-01-01`, `<= today` (theo `Asia/Ho_Chi_Minh`). Không ràng buộc với ngày tạo ví.                                                      |
| `wallet_id`        | required, thuộc user hiện tại. Khi **tạo**: phải `active`. Khi **sửa**: giữ nguyên ví cũ (mọi trạng thái) hoặc đổi sang ví `active`.                         |
| `category_id`      | required, thuộc user hiện tại, `category.type == transaction.type`. Trạng thái: rule giống `wallet_id`.                                                      |
| `note`             | nullable, ≤ 255 ký tự                                                                                                                                        |

- `user_id` luôn lấy từ user đăng nhập, **không nhận từ request**.
- Sửa giao dịch đổi Chi ↔ Thu: frontend reset ô danh mục và bắt chọn lại; backend validate lại type của danh mục.
- Sửa giao dịch chuyển sang ví **khác currency**: cho phép, nhưng không tự quy đổi. Frontend cảnh báo "Ví đích có currency khác", user phải tự nhập lại số tiền hợp lệ; backend validate số thập phân theo currency của ví mới.
- Zod schema phía frontend là schema **động** theo `decimal_places` của ví đang chọn.

### 4.6. Registration

Trong **một DB transaction** cùng với việc tạo user:

- Tạo ví mặc định: `name = "Ngân hàng"`, `type = bank`, `currency_code = VND`, `initial_balance = 0`, `status = active`.
- Tạo danh mục mặc định (`status = active`):
    - **income:** Lương, Thưởng, Thu nhập khác
    - **expense:** Ăn uống, Di chuyển, Mua sắm, Nhà ở, Hóa đơn, Giải trí, Sức khỏe, Chi phí khác

Logic này đặt trong Service (ví dụ `UserOnboardingService`), không đặt trong Controller.

### 4.7. Accounts

- Xem: tên, email, ngày đăng ký. Không bao giờ trả `password` về frontend.
- Cập nhật: **chỉ tên**. Không cho đổi email.
- Đổi mật khẩu: nhập mật khẩu hiện tại + mật khẩu mới + xác nhận. Sau khi đổi thì gọi `Auth::logoutOtherDevices()` để đăng xuất các session khác.
    - Mật khẩu mới phải **khác** mật khẩu hiện tại (rule `different:current_password`).
    - Giới hạn **5 lần/phút cho mỗi user** trên endpoint đổi mật khẩu (rate limiter `password-update`). Mục đích: khi session bị chiếm, không thể dò mật khẩu hiện tại qua form này với tốc độ không giới hạn.
- Không bắt xác thực email (cột `email_verified_at` giữ nguyên, không dùng).

---

## 5. Dashboard

- **Không có bảng riêng.** Toàn bộ là aggregate từ `transactions` + `wallets`.
- **Nhóm theo currency** (`GROUP BY wallets.currency_code`). Mỗi chỉ số hiển thị 1 dòng cho mỗi currency đang có dữ liệu, ví dụ `VND: 2.000.000 · USD: 100`.
- **Tổng thu / Tổng chi:** lọc theo khoảng thời gian đang chọn, trên `transaction_date`.
- **Tổng số dư:** luôn là số dư **hiện tại**, bỏ qua filter thời gian. Tính trên mọi ví ở mọi trạng thái.
- **10 giao dịch gần đây:** không bị ảnh hưởng bởi filter. Sắp xếp `transaction_date DESC, id DESC`. Hiển thị danh mục, ví, loại, số tiền (kèm currency), ngày, ghi chú.
- **Filter thời gian:** Hôm nay · Một ngày cụ thể · Tuần này (bắt đầu **Thứ Hai**) · Tháng này (**mặc định**) · Khoảng ngày tùy chọn.
- Logic tính khoảng thời gian dùng chung với màn hình Transactions (một class/service chung).

---

## 6. Transactions List

| Chức năng         | Quyết định                                                                                                                                               |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Tìm kiếm          | `LIKE %keyword%` trên `transactions.note`, `categories.name`, `wallets.name`. Không dùng full-text index.                                                |
| Lọc thời gian     | Một ngày · Từ ngày → đến ngày · Tuần · Tháng                                                                                                             |
| Lọc danh mục      | theo `category_id`                                                                                                                                       |
| Lọc ví            | theo `wallet_id` (cũng dùng cho màn Wallet detail → lịch sử giao dịch)                                                                                   |
| Lọc loại          | `income` / `expense`                                                                                                                                     |
| Lọc currency      | tùy chọn, theo `wallets.currency_code`                                                                                                                   |
| Lọc số tiền       | `amount_min`, `amount_max` (mỗi bên tùy chọn; `=` là min = max). So sánh theo **con số thô**, không quy đổi; UI gợi ý chọn currency khi dùng filter này. |
| Sắp xếp           | `transaction_date` hoặc `amount`, asc/desc. Mặc định `transaction_date DESC, id DESC`.                                                                   |
| Phân trang        | cố định **20** / trang                                                                                                                                   |
| Trạng thái filter | lưu trên query string (bookmark/chia sẻ được)                                                                                                            |
| Hiển thị          | ID, loại, số tiền + currency, ngày, danh mục, ví, ghi chú, thời gian tạo                                                                                 |
| Chi tiết          | Loại, số tiền, danh mục, ví, ngày giao dịch, ghi chú, `created_at`, `updated_at`. Không có "Người tạo" / "Trạng thái".                                   |
| Form tạo          | Tự chọn sẵn **ví được dùng gần nhất** (lấy từ giao dịch mới nhất của user, chỉ khi ví đó còn `active`). Không có cột `is_default`.                       |

---

## 7. Thứ tự triển khai đề xuất

1. **Cấu hình:** đổi timezone sang `Asia/Ho_Chi_Minh`; cấu hình DB test MySQL (`phpunit.xml` / `.env.testing`).
2. **Enums:** `TransactionType`, `EntityStatus`, `WalletType`.
3. **Migrations** theo thứ tự §3 (kèm CHECK constraint).
4. **Models:** `Currency` (PK string `code`, `$incrementing = false`, `$keyType = 'string'`), `Wallet`, `Category`, `Transaction`; quan hệ `User` → `wallets()`, `categories()`, `transactions()`; casts enum + `decimal:4`.
5. **Seeders & factories:** `CurrencySeeder`, factories cho Wallet/Category/Transaction.
6. **Registration onboarding:** Service tạo ví + danh mục mặc định trong DB transaction.
7. **Wallets → Categories → Transactions → Dashboard → Account**, mỗi module theo workflow CLAUDE.md: FormRequest → Service → Controller/route → Inertia page → Zod schema.

---

## 8. Checklist edge case cần có test

- [ ] Sửa giao dịch `100.000 Chi → 200.000 Chi`: số dư ví giảm thêm 100.000.
- [ ] Sửa giao dịch đổi Ví A → Ví B: số dư A được hoàn lại, B bị trừ.
- [ ] Sửa giao dịch đổi Chi → Thu mà không đổi danh mục: bị reject.
- [ ] Tạo giao dịch với ví/danh mục `inactive` hoặc `archived`: bị reject.
- [ ] Sửa giao dịch đang gắn ví `archived`, giữ nguyên ví: được phép.
- [ ] Tạo giao dịch với ví/danh mục của user khác: 404/validation fail.
- [ ] Số tiền `10.5` cho ví VND: bị reject. `10.50` cho ví USD: hợp lệ.
- [ ] Chuyển giao dịch từ ví USD (`10.50`) sang ví VND mà không sửa số tiền: bị reject.
- [ ] `transaction_date` ở tương lai hoặc trước `2000-01-01`: bị reject.
- [ ] Xóa ví/danh mục đã có giao dịch: bị chặn (cả ở Service và FK).
- [ ] Đổi currency / `initial_balance` của ví đã có giao dịch: bị chặn.
- [ ] Đổi `type` của danh mục đã có giao dịch: bị chặn.
- [ ] Tạo danh mục "luong" (income) khi đã có "Lương" (income, kể cả archived): bị reject.
- [ ] Tạo ví trùng tên (khác hoa/thường): bị reject.
- [ ] Chi vượt số dư: được phép, ví âm.
- [ ] Dashboard với ví VND + USD: tổng tách theo currency, không cộng lẫn.
- [ ] Dashboard: số dư không đổi khi thay filter thời gian.
- [ ] Đăng ký: tạo đúng 1 ví "Ngân hàng" + 11 danh mục; lỗi giữa chừng thì rollback toàn bộ.
- [x] Đổi mật khẩu: các session khác bị đăng xuất.
- [x] Cập nhật tài khoản: gửi kèm email thì email không đổi.
- [ ] Truy cập `/transactions/{id}` của user khác: 404.
