# Task — Wallets (Frontend)

> Nguồn: [`features/phase-1.md`](../../features/phase-1.md) §4, §6.5 + [`phases/phase-1.md`](../../phases/phase-1.md) §1, §4.2, §4.3 + [`tasks/wallets/backend.md`](backend.md) + template Trezo `teams.html` + buổi grill Q1–Q16.
> Phạm vi: **chỉ frontend**, không sửa file PHP nào. Chỉ bắt đầu **sau khi** [backend](backend.md) đã merge.
> Nhánh: `feat/implement-wallets-fe` tách từ `main`. Một nhánh, **mỗi task một commit**, mở một PR khi xong cả 6 task (Q1).

---

## 0. Tình trạng hiện tại

| Thành phần                                                                    | Trạng thái                                                       |
| ----------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| 5 route `wallets.*`, props `wallets` / `currencies` / `filters`               | ⏳ do [backend](backend.md) T2–T6 tạo ra; contract ở §3 bên dưới |
| `Components/Common/Modal.tsx`, `FlashToasts.tsx`                              | ✅ có (Categories)                                               |
| `IN_PLACE_SUBMIT`, `IN_PLACE_FILTER` (`lib/inPlaceSubmit.ts`)                 | ✅ có                                                            |
| `fieldError()`, `useTranslation()`, `trans()`, `INTL_LOCALES`, `formatDate()` | ✅ có                                                            |
| Nhãn / màu / mô tả trạng thái, `badgeClass` (`lib/categoryLabels.ts`)         | ⚠️ có nhưng **gắn với Categories** → FE1 tách ra                 |
| Đóng dropdown khi click ra ngoài (`Header.tsx:26`, `LanguageSwitcher.tsx:20`) | ⚠️ **chép lặp 2 nơi** → FE1 tách thành hook                      |
| Page, component, schema, type, tiền tệ, sidebar cho Wallets                   | ❌ **FE2–FE6**                                                   |

Sự thật đã xác minh trong lúc grill:

- `Intl.NumberFormat.prototype.format()` nhận **chuỗi** và giữ đủ chữ số: `"123456789012345678.0000"` → `123.456.789.012.345.678 ₫`. Hiển thị tiền **không cần** parse sang `number`.
- `tsconfig.json` đang là `lib: ["ES2022", …]`: type của `format()` chỉ nhận `number | bigint`. Phải thêm `ES2023` (FE2).
- `narrowSymbol`: `-12,50 $` (vi, USD), `₫1,000,000` (en, VND), khớp ví dụ ở plan §1 và feature §6.5. USD/SGD/AUD đều ra `$`, JPY/CNY đều ra `¥` → thẻ luôn ghi kèm **mã** currency.
- Zod **4.5.4**: `superRefine` trên object **vẫn chạy** khi field khác đã lỗi (đã thử) → lỗi số tiền hiện cùng lúc với lỗi tên.
- `fieldError(t, errors, serverError)` dịch message Zod bằng `t(message)` **không có replacements** → message Zod của frontend **không** dùng placeholder (xem §4).
- `lang/vi.json` đã có `Bank`, `Show archived`, `Change status`, `Edit`, `Delete`, `Cancel`, `Save`, `Status`, `Name`, `Type`, `Transactions`, `Please choose a type`, `Delete ":name"? This cannot be undone.`.
- Dropdown trong dự án do React state điều khiển; không dùng `data-bs-*` / `window.bootstrap` (rule `frontend.md`).
- Dự án **chưa có** framework test frontend.

---

## 1. Bảng quyết định (Q1–Q16)

| #   | Quyết định                                                                                                                                                                                                                    |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Q1  | Spec chung nhánh `feat/create-spec-wallets` với backend. Code trên `feat/implement-wallets-fe`, sau khi backend merge. Mỗi task một commit, một PR.                                                                           |
| Q2  | Danh sách dạng **lưới thẻ** theo `teams.html`, không phải bảng.                                                                                                                                                               |
| Q3  | FE1 là commit refactor: `EntityStatus` + nhãn/màu/mô tả trạng thái + `badgeClass` thành module dùng chung. Modal đổi trạng thái / xóa **mỗi domain giữ bản riêng**.                                                           |
| Q4  | `formatMoney()` dùng `Intl.NumberFormat` + `currencyDisplay: 'narrowSymbol'`, truyền **chuỗi**, cố định số chữ số thập phân theo `decimal_places`. Thêm `ES2023` vào `lib`. Không dùng `currencies.symbol`.                   |
| Q5  | Ô số tiền: `type="text" inputMode="decimal"`, chỉ chữ số + tối đa một dấu `.`, **không** chèn dấu phân nhóm khi gõ. Dưới ô có **dòng xem trước** đã format.                                                                   |
| Q7  | Bố cục thẻ: icon theo loại · tên · "loại · mã currency" · menu ⋯ · pill trạng thái · số dư hiện tại lớn (đỏ khi âm) · số dư ban đầu / số giao dịch / ngày tạo · mô tả. Xem §5.                                                |
| Q8  | Thao tác nằm trong dropdown `more_horiz` (Edit / Change status / Delete). Mục bị chặn **mờ + dòng lý do**. FE1 tách hook `useDismiss`. **Không** tách `ActionButton`.                                                         |
| Q9  | Không có nút "View Details". Nút "Xem giao dịch" (`/transactions?wallet_id=…`) thêm ở task Transactions.                                                                                                                      |
| Q10 | Toolbar không bọc card: "Show archived" (trái) + "Add New Wallet" (phải). Lưới `col-xxl-3 col-lg-4 col-sm-6` **căn trái**. Rỗng → card "No wallets found.".                                                                   |
| Q11 | Icon + màu theo loại ví ở `lib/walletLabels.ts` (bảng §4.4).                                                                                                                                                                  |
| Q12 | Modal Thêm: Name → Type (mặc định `other`) → Currency (chỉ active, nhãn `VND · Việt Nam Đồng`, mặc định `VND`) → Initial balance (`"0"`) → Description (textarea).                                                            |
| Q13 | Modal Sửa dùng chung component. Ví có giao dịch → Currency + Initial balance `disabled` + hint. Currency đã tắt mà ví đang dùng vẫn có trong select, kèm "(inactive)". Prefill cắt theo `decimal_places` bằng thao tác chuỗi. |
| Q14 | `lib/money.ts`: `formatMoney`, `toAmountInput`, `amountIssue`. `Schemas/wallet.schema.ts`: `makeWalletFormSchema(currencies)` + `superRefine`. Không hàm nào parse tiền sang `number`.                                        |
| Q15 | Sidebar: **Wallets** trong nhóm MAIN, **trước** Categories, icon `account_balance_wallet`.                                                                                                                                    |
| Q16 | `teams.html` ở gốc repo **không** commit; chỉ là tài liệu tham khảo.                                                                                                                                                          |

---

## 2. Danh sách file theo task

| Task | Tạo mới                                                                                                                                              | Sửa                                                                                                                                                                                                                                |
| ---- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| FE1  | `types/common.types.ts`, `lib/entityStatus.ts`, `hooks/useDismiss.ts`                                                                                | `types/index.d.ts`, `types/category.types.ts`, `lib/categoryLabels.ts`, `Pages/Categories/CategoryTable.tsx`, `Pages/Categories/CategoryStatusModal.tsx`, `Components/Layout/Header.tsx`, `Components/Common/LanguageSwitcher.tsx` |
| FE2  | `types/currency.types.ts`, `types/wallet.types.ts`, `lib/money.ts`, `lib/walletLabels.ts`, `Pages/Wallets/Index.tsx`, `Pages/Wallets/WalletCard.tsx` | `tsconfig.json`, `types/index.d.ts`, `Config/sidebarMenu.ts`, `lang/vi.json`                                                                                                                                                       |
| FE3  | `Schemas/wallet.schema.ts`, `Pages/Wallets/WalletFormModal.tsx`                                                                                      | `Schemas/index.ts`, `lib/money.ts` (+ `amountIssue`), `Pages/Wallets/Index.tsx`, `lang/vi.json`                                                                                                                                    |
| FE4  | —                                                                                                                                                    | `lib/money.ts` (+ `toAmountInput`), `Pages/Wallets/WalletFormModal.tsx`, `Pages/Wallets/Index.tsx`, `lang/vi.json`                                                                                                                 |
| FE5  | `Pages/Wallets/WalletStatusModal.tsx`                                                                                                                | `Pages/Wallets/Index.tsx`                                                                                                                                                                                                          |
| FE6  | `Pages/Wallets/DeleteWalletModal.tsx`                                                                                                                | `Pages/Wallets/Index.tsx`, `lang/vi.json`, `docs/README.md`                                                                                                                                                                        |

Mọi đường dẫn tính từ `resources/js/`, trừ `tsconfig.json`, `lang/vi.json` và `docs/README.md`.

**Không đụng tới:** file PHP, `Modal.tsx`, `FlashToasts.tsx`, `inPlaceSubmit.ts`, `FormField.tsx`, các trang Account/Login/Register/Dashboard.

---

## 3. Contract với backend (tham chiếu)

Props của `Wallets/Index`: xem [`backend.md`](backend.md) §3. Tiền là **string** `decimal:4` (`"1000000.0000"`, có thể âm ở `current_balance`).

| Thao tác       | Visit                                                      | Payload                                                           | Kết quả                                                                           |
| -------------- | ---------------------------------------------------------- | ----------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| Xem danh sách  | `router.get('/wallets', query, IN_PLACE_FILTER)`           | `include_archived?`                                               | props `wallets`, `currencies`, `filters`                                          |
| Thêm           | `router.post('/wallets', …, IN_PLACE_SUBMIT)`              | `name`, `type`, `currency_code`, `initial_balance`, `description` | toast `Wallet created.` / lỗi field                                               |
| Sửa            | `router.patch('/wallets/{id}', …, IN_PLACE_SUBMIT)`        | như Thêm (**luôn gửi đủ**, kể cả field bị khóa)                   | toast `Wallet updated.` / lỗi field / lỗi `status` (ví vừa bị archive ở tab khác) |
| Đổi trạng thái | `router.patch('/wallets/{id}/status', …, IN_PLACE_SUBMIT)` | `status`                                                          | toast `Wallet status updated.` / lỗi `status`                                     |
| Xóa            | `router.delete('/wallets/{id}', IN_PLACE_SUBMIT)`          | –                                                                 | toast `Wallet deleted.` **hoặc** toast lỗi `flash.error` (ví đang có giao dịch)   |

- Mọi mutation trả `back()` → query string giữ nguyên → "Show archived" không bị reset.
- Xóa bị chặn vẫn là **redirect thành công** → modal xóa đóng ở `onFinish`.
- Lỗi từ server đã được dịch sẵn → hiển thị nguyên văn, **không** qua `t()`.
- `description` rỗng: gửi `''`, middleware `ConvertEmptyStringsToNull` của Laravel đổi thành `null`.

---

## 4. Module dùng chung

### 4.1. `types/common.types.ts` (FE1)

```ts
/** Mirrors App\Enums\EntityStatus, shared by wallets and categories. */
export type EntityStatus = 'active' | 'inactive' | 'archived';
```

`category.types.ts`: **xóa** `CategoryStatus`, `Category.status` đổi thành `EntityStatus`. Các nơi đang import `CategoryStatus` (`CategoryStatusModal.tsx`) đổi sang `EntityStatus`.

### 4.2. `lib/entityStatus.ts` (FE1)

Chuyển **nguyên văn** từ `categoryLabels.ts`, chỉ đổi tên. Chuỗi giữ nguyên nên **không** đổi key dịch nào.

| Cũ (`categoryLabels.ts`)       | Mới (`entityStatus.ts`)      |
| ------------------------------ | ---------------------------- |
| `CATEGORY_STATUS_LABELS`       | `ENTITY_STATUS_LABELS`       |
| `CATEGORY_STATUS_DESCRIPTIONS` | `ENTITY_STATUS_DESCRIPTIONS` |
| `CATEGORY_STATUS_COLORS`       | `ENTITY_STATUS_COLORS`       |
| `badgeClass()`                 | `badgeClass()`               |

`categoryLabels.ts` chỉ còn `CATEGORY_TYPE_LABELS` + `CATEGORY_TYPE_COLORS`. Mô tả trạng thái nhắc tới "transactions" và "Show archived", đúng cho cả ví lẫn danh mục (plan §4.2 áp dụng chung).

### 4.3. `hooks/useDismiss.ts` (FE1)

```ts
/**
 * Close a dropdown when the user clicks outside it or presses Escape.
 * Listeners are attached only while it is open.
 */
export function useDismiss(ref: RefObject<HTMLElement | null>, isOpen: boolean, onDismiss: () => void): void;
```

- Lấy logic từ `LanguageSwitcher.tsx` (`mousedown` + `keydown` Escape, chỉ gắn khi `isOpen`).
- Giữ `onDismiss` trong một ref (cập nhật mỗi render), effect chỉ phụ thuộc `isOpen`: caller truyền arrow inline, nếu đưa thẳng vào deps thì listener bị gỡ/gắn lại ở mỗi render khi menu đang mở.
- `LanguageSwitcher`: thay `useEffect` bằng `useDismiss(rootRef, isOpen, () => setIsOpen(false))`.
- `Header`: hai lần gọi, cho notification và profile. **Thay đổi hành vi có chủ đích:** hai dropdown của Header giờ đóng được bằng Esc và chỉ gắn listener khi đang mở.
- Chỉ áp dụng cho **dropdown**. Modal vẫn **không** đóng bằng Esc (rule `frontend.md`).

### 4.4. `lib/walletLabels.ts` (FE2)

```ts
export const WALLET_TYPE_LABELS: Record<WalletType, string> = {
    cash: trans('Cash'),
    bank: trans('Bank'),
    e_wallet: trans('E-wallet'),
    credit_card: trans('Credit card'),
    other: trans('Other'),
};
```

| Type          | Icon (Material Symbols)  | Màu         |
| ------------- | ------------------------ | ----------- |
| `cash`        | `payments`               | `success`   |
| `bank`        | `account_balance`        | `primary`   |
| `e_wallet`    | `smartphone`             | `info`      |
| `credit_card` | `credit_card`            | `warning`   |
| `other`       | `account_balance_wallet` | `secondary` |

→ `WALLET_TYPE_ICONS`, `WALLET_TYPE_COLORS`. Nhãn khớp `App\Enums\WalletType::label()` để hai bên dùng cùng key dịch.

### 4.5. `lib/money.ts`

Mọi hàm làm việc trên **chuỗi**; không hàm nào gọi `Number()` / `parseFloat()` trên số tiền (plan §1).

```ts
/**
 * Format a decimal string from the backend in the UI language, e.g.
 * "-12.5000" USD → "-12,50 $" (vi) and "1000000.0000" VND → "₫1,000,000" (en).
 */
export function formatMoney(amount: string, currency: Currency, locale: Locale): string; // FE2

/** "1000000.0000" → "1000000" (VND), "10.5000" → "10.50" (USD): the stored value as the form shows it. */
export function toAmountInput(amount: string, decimalPlaces: number): string; // FE4

/** The English source message of the first problem with a typed amount, or null when it is valid. */
export function amountIssue(value: string, currency: Currency | undefined): string | null; // FE3
```

**`formatMoney`**

- `new Intl.NumberFormat(INTL_LOCALES[locale], { style: 'currency', currency: currency.code, currencyDisplay: 'narrowSymbol', minimumFractionDigits: dp, maximumFractionDigits: dp })`, với `dp = currency.decimal_places`.
- `format(amount as Intl.StringNumericLiteral)`: truyền **chuỗi**, cần `lib: ES2023`.
- Cache formatter theo `${locale}:${code}` trong một `Map` ở module (khuôn `DATE_FORMATS` của `formatDate.ts`).
- Backend đảm bảo số chữ số thập phân ≤ `dp`, nên `maximumFractionDigits = dp` không bao giờ làm tròn mất giá trị.

**`toAmountInput`**: tách tại `.`, giữ phần nguyên; `dp = 0` → chỉ phần nguyên; `dp > 0` → `frac.slice(0, dp)`. Không bỏ số 0 cuối (`"10.50"` giữ nguyên để khớp `decimal_places`).

**`amountIssue`**: `trim()` giá trị **một lần** ở đầu hàm (khớp middleware `TrimStrings` của Laravel, nên `" 100"` hợp lệ), mọi kiểm tra dưới đây chạy trên chuỗi đã trim, theo thứ tự, trả message **đầu tiên** gặp phải:

| Điều kiện                                                                                                    | Message (English key)                           |
| ------------------------------------------------------------------------------------------------------------ | ----------------------------------------------- |
| rỗng                                                                                                         | `Enter an amount.`                              |
| không khớp `^\d+(\.\d+)?$`                                                                                   | `Use digits only, with a dot (.) for decimals.` |
| phần nguyên (bỏ số 0 đầu) dài hơn 12 chữ số, **hoặc** bằng `999999999999` mà phần thập phân có chữ số khác 0 | `The amount is too large.`                      |
| `currency` là `undefined`                                                                                    | `null` (lỗi đã nằm ở ô Currency)                |
| phần thập phân dài hơn `dp`, `dp = 0`                                                                        | `This currency does not use decimals.`          |
| phần thập phân dài hơn `dp`, `dp > 0`                                                                        | `Too many decimal places for this currency.`    |

- Khớp backend: `numeric` + `min:0` (không cho dấu `-`), `max:999999999999` (so sánh **giá trị**, nên `"999999999999.50"` USD cũng vượt; chỉ đếm chữ số phần nguyên thì frontend cho qua còn backend trả 422), `MoneyPrecision` (đếm chữ số thập phân **viết ra**, nên `"10.0"` với VND bị từ chối).
- ⚠️ **Lệch có chủ đích so với grill Q14:** message frontend **không** dùng chung key `The :attribute must be a whole number for :currency.` của backend, vì `fieldError()` dịch không có replacements. Message frontend không có placeholder; ngữ cảnh (tên field, currency) đã nằm sẵn trên form. Lỗi từ server vẫn là message đầy đủ của backend.
- Transactions sẽ dùng lại `amountIssue` cho `amount` (thêm kiểm tra `> 0` ở schema của nó).

---

## 5. Thẻ ví (`Pages/Wallets/WalletCard.tsx`)

Props: `wallet: Wallet`, `currency: Currency | undefined`, `onEdit`, `onChangeStatus`, `onDelete`. Thẻ **hoàn chỉnh từ FE2**: mỗi mục menu gọi callback tương ứng với `wallet`; FE4–FE6 chỉ thêm modal và render nó trong `Index.tsx`, không sửa thẻ.

Markup bám `teams.html` (dòng 1871–1944), thay ảnh và progress bar:

```text
col-xxl-3 col-lg-4 col-sm-6
└─ card bg-white border-0 rounded-3 mb-4 > card-body p-4
   ├─ d-flex justify-content-between align-items-center mb-3
   │  ├─ d-flex align-items-center
   │  │  ├─ flex-shrink-0 > wh-65 rounded-circle d-flex align-items-center justify-content-center bg-{màu} bg-opacity-10
   │  │  │                  > i.material-symbols-outlined.fs-28.text-{màu}   {WALLET_TYPE_ICONS[type]}
   │  │  └─ flex-grow-1 ms-2 position-relative top-2
   │  │     ├─ h4.fs-16.fw-semibold.mb-1   {name}
   │  │     └─ span                        {t(WALLET_TYPE_LABELS[type])} · {currency_code}
   │  └─ CardMenu (§5.1)
   ├─ span.d-block.py-2.px-3.text-center.rounded-pill.fw-medium.mb-3.bg-{c}.bg-opacity-10.text-{c}
   │                                         {t(ENTITY_STATUS_LABELS[status])}, c = ENTITY_STATUS_COLORS
   ├─ span.d-block.text-center.text-body.mb-1     {t('Current balance')}
   ├─ span.d-block.text-center.fs-20.fw-bold.mb-3[.text-danger]   {formatMoney(current_balance)}
   ├─ d-flex justify-content-between mb-2   {t('Initial balance')}   | span.fw-medium {formatMoney(initial_balance)}
   ├─ d-flex justify-content-between mb-2   {t('Transactions')}      | span.fw-medium {transactions_count}
   ├─ d-flex justify-content-between        {t('Created')}           | span.fw-medium {formatDate(created_at, locale)}
   └─ p.fs-14.text-secondary.mt-3.mb-0 (chỉ khi có description)   {description}
```

- Số âm: `current_balance.startsWith('-')` → thêm `text-danger`. So sánh chuỗi, không parse.
- Mô tả cắt **2 dòng**: `style={{ display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}` + `title={description}` để xem đủ.
- Pill thay `style="background-color: …"` cứng của template bằng class màu theo trạng thái.
- `currency` không tìm thấy (không xảy ra với dữ liệu hợp lệ) → hiện chuỗi thô kèm mã, không crash.
- `formatDate(created_at, locale)`: `created_at` là `"2026-10-02"`; `new Date("2026-10-02")` là nửa đêm **UTC**, format theo `Asia/Ho_Chi_Minh` (UTC+7) vẫn ra đúng ngày 02/10. Kiểm tra lại trong checklist §8.

### 5.1. `CardMenu` (component nội bộ của `WalletCard.tsx`)

- Markup template: `div.dropdown.action-opt.ms-2.position-relative.top-3` > `button.p-0.border-0.bg-transparent` (`aria-label={t('More options')}`, `aria-expanded`, `aria-haspopup="menu"`) > `i.material-symbols-outlined.fs-20.text-body.hover` `more_horiz`.
- Menu: chỉ render khi mở, `ul.dropdown-menu.dropdown-menu-end.bg-white.border.box-shadow.show.d-block.position-absolute.end-0.top-100.mt-1` (khuôn `Header.tsx:118`). `useState` + `useDismiss(rootRef, isOpen, close)`. Không `data-bs-*`.
    - ⚠️ `end-0 top-100` là **bắt buộc**: `.dropdown-menu-end` chỉ đặt `right: 0` khi có `[data-bs-popper]` (`style.css:5411`), thuộc tính do Popper của Bootstrap gắn mà dự án không dùng. Thiếu hai class này, menu mọc từ nút ⋯ ở mép phải thẻ và tràn sang phải, bị `overflow-hidden` của container cắt ở cột cuối và trên mobile.
- Mỗi mục là `<li>` > `<button type="button" className="dropdown-item d-flex align-items-start gap-2">` (CSS template chỉ áp cho `.action-opt .dropdown-menu li .dropdown-item`). Bên trong: icon `material-symbols-outlined fs-18` (`edit`, `toggle_on`, `delete`) + **một** `<span>` bọc nhãn (và dòng lý do nếu có). Bấm một mục → đóng menu → gọi callback.
- Mục bị chặn: thuộc tính `disabled` + dòng lý do `span.d-block.fs-12.text-secondary.text-wrap` **trong** `<span>` bọc nhãn (Bootstrap tự làm mờ `.dropdown-item:disabled`).
    - ⚠️ Template đặt `.dropdown-item` là `display: flex` và `white-space: nowrap`: nếu lý do là con trực tiếp của button, nó nằm **cạnh** nhãn thay vì bên dưới, và câu dài kéo menu rất rộng. Wrapper + `text-wrap` giải quyết cả hai.

| Mục           | Bị chặn khi              | Lý do (English key)                            |
| ------------- | ------------------------ | ---------------------------------------------- |
| Edit          | `status === 'archived'`  | `Restore this wallet before editing.`          |
| Change status | không bao giờ            | –                                              |
| Delete        | `transactions_count > 0` | `Wallets with transactions cannot be deleted.` |

Ví archived **và** có giao dịch → chỉ còn Change status. Frontend chỉ phản ánh rule cho UX; backend vẫn chặn thật.

---

## 6. Message và bản dịch (frontend)

Thêm vào `lang/vi.json` (giữ thứ tự alphabet), mỗi task thêm đúng key của mình. Key backend nằm ở [`backend.md`](backend.md) §4.

| Key (English)                                                     | Tiếng Việt                                               | Task |
| ----------------------------------------------------------------- | -------------------------------------------------------- | ---- |
| `Wallets`                                                         | `Ví`                                                     | FE2  |
| `Add New Wallet`                                                  | `Thêm ví`                                                | FE2  |
| `No wallets found.`                                               | `Không có ví nào.`                                       | FE2  |
| `Current balance`                                                 | `Số dư hiện tại`                                         | FE2  |
| `Initial balance`                                                 | `Số dư ban đầu`                                          | FE2  |
| `Created`                                                         | `Ngày tạo`                                               | FE2  |
| `More options`                                                    | `Tùy chọn khác`                                          | FE2  |
| `Cash`                                                            | `Tiền mặt`                                               | FE2  |
| `E-wallet`                                                        | `Ví điện tử`                                             | FE2  |
| `Credit card`                                                     | `Thẻ tín dụng`                                           | FE2  |
| `Other`                                                           | `Khác`                                                   | FE2  |
| `Restore this wallet before editing.`                             | `Cần khôi phục ví trước khi sửa.`                        | FE2  |
| `Wallets with transactions cannot be deleted.`                    | `Không thể xóa ví đã có giao dịch.`                      | FE2  |
| `Currency`                                                        | `Tiền tệ`                                                | FE3  |
| `Description`                                                     | `Mô tả`                                                  | FE3  |
| `Enter wallet name`                                               | `Nhập tên ví`                                            | FE3  |
| `Optional`                                                        | `Không bắt buộc`                                         | FE3  |
| `Wallet name is required`                                         | `Vui lòng nhập tên ví`                                   | FE3  |
| `Wallet name must not exceed 100 characters`                      | `Tên ví không được dài quá 100 ký tự`                    | FE3  |
| `Please choose a currency`                                        | `Vui lòng chọn tiền tệ`                                  | FE3  |
| `Description must not exceed 255 characters`                      | `Mô tả không được dài quá 255 ký tự`                     | FE3  |
| `Enter an amount.`                                                | `Vui lòng nhập số tiền.`                                 | FE3  |
| `Use digits only, with a dot (.) for decimals.`                   | `Chỉ nhập chữ số, dùng dấu chấm (.) cho phần thập phân.` | FE3  |
| `The amount is too large.`                                        | `Số tiền quá lớn.`                                       | FE3  |
| `This currency does not use decimals.`                            | `Tiền tệ này không có phần thập phân.`                   | FE3  |
| `Too many decimal places for this currency.`                      | `Quá nhiều chữ số thập phân cho tiền tệ này.`            | FE3  |
| `Edit Wallet`                                                     | `Sửa ví`                                                 | FE4  |
| `:currency (inactive)`                                            | `:currency (ngừng hỗ trợ)`                               | FE4  |
| `Currency is locked because this wallet has transactions.`        | `Không đổi được tiền tệ vì ví đã có giao dịch.`          | FE4  |
| `Initial balance is locked because this wallet has transactions.` | `Không đổi được số dư ban đầu vì ví đã có giao dịch.`    | FE4  |
| `Delete Wallet`                                                   | `Xóa ví`                                                 | FE6  |

- Không dịch dữ liệu người dùng (tên ví, mô tả) và tên currency từ DB (`Việt Nam Đồng`, `US Dollar`).
- Nhãn có biến dùng placeholder (`:currency (inactive)`), không nối chuỗi (rule `localization.md`).

---

## 7. Tasks

Mỗi task một commit; mỗi commit tự pass:

```
npx tsc --noEmit → npm run format → npm run format:check → npm run build → npm run lang:check
```

Trước khi sửa symbol đã tồn tại, chạy impact theo `CLAUDE.md`: `node .gitnexus/run.cjs impact "<symbol>" --direction upstream --repo .`. Trước khi commit: `node .gitnexus/run.cjs detect-changes --scope all --repo .` (không chấp nhận `partial` / `truncated`). Comment tiếng Anh, giải thích **vì sao**, mật độ như `CategoryFormModal.tsx`.

### FE1 — Refactor phần dùng chung

**Commit:** `refactor(frontend): share entity status labels and dropdown dismissal`
**Phụ thuộc:** —

**Impact trước khi sửa:** `CATEGORY_STATUS_LABELS`, `CATEGORY_STATUS_COLORS`, `CATEGORY_STATUS_DESCRIPTIONS`, `badgeClass`, `CategoryStatus`, `Header`, `LanguageSwitcher`.

**Việc:** §4.1, §4.2, §4.3. `types/index.d.ts` thêm `export * from './common.types';`.

**Tiêu chí xong:** không còn tham chiếu nào tới tên cũ: `rg "\bCategoryStatus\b|CATEGORY_STATUS_" resources/js` không ra kết quả (`\b` để bỏ qua `CategoryStatusModal`, `updateCategoryStatusSchema`, `UpdateCategoryStatusFormValues`, vốn giữ nguyên); `lang/vi.json` **không đổi**; giao diện Categories y hệt trước.

**Test tay**

- [ ] Categories: badge trạng thái đúng màu, đúng chữ ở cả vi và en.
- [ ] Categories → modal Change Status: mô tả từng trạng thái vẫn hiện đúng.
- [ ] Header: dropdown Notifications và Profile mở/đóng bằng click; click ra ngoài → đóng; **Esc → đóng** (mới).
- [ ] LanguageSwitcher: mở, click ra ngoài, Esc, chọn ngôn ngữ đều như cũ.
- [ ] Mở một modal Categories, nhấn Esc → modal **không** đóng (không hồi quy).

### FE2 — Danh sách ví

**Commit:** `feat(wallets): list wallets as cards`
**Phụ thuộc:** FE1, backend T2

**Việc**

1. `tsconfig.json`: `lib` thêm `"ES2023"`.
2. `types/currency.types.ts`, `types/wallet.types.ts`, re-export trong `types/index.d.ts`:

    ```ts
    /** One row of the `currencies` prop sent by WalletController@index. */
    export interface Currency {
        code: string;
        name: string;
        symbol: string;
        decimal_places: number;
        is_active: boolean;
    }
    ```

    ```ts
    /** Mirrors App\Enums\WalletType. */
    export type WalletType = 'cash' | 'bank' | 'e_wallet' | 'credit_card' | 'other';

    /** One row of the `wallets` prop sent by WalletController@index. Money is a decimal string. */
    export interface Wallet {
        id: number;
        name: string;
        type: WalletType;
        currency_code: string;
        initial_balance: string;
        current_balance: string;
        description: string | null;
        status: EntityStatus;
        transactions_count: number;
        created_at: string; // "Y-m-d", Vietnam time
    }

    /** The `filters` prop, echoed back so the checkbox reflects the URL. */
    export interface WalletFilters {
        include_archived: boolean;
    }
    ```

3. `lib/walletLabels.ts` (§4.4), `lib/money.ts` với `formatMoney` (§4.5).
4. `Pages/Wallets/WalletCard.tsx` (§5), hoàn chỉnh: menu đủ 3 mục với trạng thái disabled, mỗi mục gọi `onEdit` / `onChangeStatus` / `onDelete`. `Index.tsx` truyền `(wallet) => setModal({ kind: 'edit' | 'status' | 'delete', wallet })` ngay từ FE2; chưa có modal nào render nên bấm vào chưa có gì xảy ra, FE4–FE6 chỉ thêm phần render.
5. `Pages/Wallets/Index.tsx`:
    - `type IndexProps = PageProps<{ wallets: Wallet[]; currencies: Currency[]; filters: WalletFilters }>`.
    - `<Head>`, `<Breadcrumb title={t('Wallets')} items={[Dashboard, Wallets]} />`, `<FlashToasts />`.
    - Toolbar `d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4` (không bọc card): checkbox "Show archived" (khuôn Categories) + nút "Add New Wallet" (class và icon `ri-add-line` như nút "Add New Category"; FE3 mới nối modal).
    - `currencyByCode = useMemo(() => new Map(currencies.map((c) => [c.code, c])), [currencies])`.
    - Lưới `div.row` > `WalletCard` theo thứ tự backend gửi (**không** sort lại). Rỗng → `card bg-white border-0 rounded-3 mb-4` > `card-body p-4 text-center text-secondary` "No wallets found.".
    - Filter: `router.get('/wallets', include_archived ? { include_archived: '1' } : {}, IN_PLACE_FILTER)`; checkbox đọc từ prop `filters`, không giữ state riêng.
    - `ModalState` (`useState`, không Zustand) khai báo đủ 5 kind ngay từ đầu; FE3–FE6 lần lượt render modal.
6. `Config/sidebarMenu.ts`: thêm `{ id: 'wallets', title: trans('Wallets'), icon: 'account_balance_wallet', url: '/wallets' }` **trước** mục `categories`. Impact trước: `sidebarMenuConfig`.

**Test tay** (chuẩn bị: user mới có ví "Ngân hàng"; tạo thêm qua tinker một ví USD có giao dịch chi vượt số dư, một ví `inactive`, một ví `archived`)

- [ ] Sidebar: **Wallets** đứng trước Categories, tới `/wallets`.
- [ ] Mỗi thẻ đúng bố cục §5: icon/màu theo loại, "Ngân hàng · VND", pill trạng thái, 3 dòng nhãn–giá trị.
- [ ] Ví VND: `1.000.000 ₫` (vi), `₫1,000,000` (en). Ví USD: `12,50 $` (vi), `$12.50` (en).
- [ ] Số dư âm in **đỏ**, có dấu `-`.
- [ ] Ngày tạo đúng ngày theo giờ VN, định dạng `dd/MM/yyyy` ở cả hai ngôn ngữ.
- [ ] Mô tả dài bị cắt 2 dòng; hover thấy đủ.
- [ ] Ví archived ẩn mặc định; bật "Show archived" → hiện, URL `?include_archived=1`, có `LoadingOverlay`, không chớp Preloader.
- [ ] Reload tại `?include_archived=1` → checkbox đang bật.
- [ ] Menu ⋯: mở/đóng bằng click, click ra ngoài, Esc. Ví archived → Edit mờ + lý do **nằm dưới** nhãn, xuống dòng khi dài; ví có giao dịch → Delete mờ + lý do.
- [ ] Menu ⋯ căn theo **mép phải** nút, không bị cắt ở thẻ cột cuối và trên mobile.
- [ ] Không có ví nào (tắt "Show archived" khi mọi ví đều archived) → card "No wallets found."
- [ ] Lưới: 4 cột ≥ xxl, 3 cột ≥ lg, 2 cột ≥ sm, 1 cột mobile; 1–2 ví thì căn trái.

### FE3 — Modal Thêm ví

**Commit:** `feat(wallets): add wallet form`
**Phụ thuộc:** FE2, backend T3

**`Schemas/wallet.schema.ts`** (mở đầu bằng comment nêu FormRequest phải khớp: `StoreWalletRequest` / `UpdateWalletRequest`):

```ts
export function makeWalletFormSchema(currencies: Currency[]) {
    return z
        .object({
            name: z.string().trim().min(1, trans('Wallet name is required')).max(100, trans('Wallet name must not exceed 100 characters')),
            type: z.enum(['cash', 'bank', 'e_wallet', 'credit_card', 'other'], { error: trans('Please choose a type') }),
            currency_code: z.string().min(1, trans('Please choose a currency')),
            initial_balance: z.string(),
            description: z.string().max(255, trans('Description must not exceed 255 characters')),
        })
        .superRefine((value, ctx) => {
            const currency = currencies.find((c) => c.code === value.currency_code);
            const issue = amountIssue(value.initial_balance, currency);

            if (issue) {
                ctx.addIssue({ code: 'custom', path: ['initial_balance'], message: issue });
            }
        });
}

export type WalletFormValues = z.infer<ReturnType<typeof makeWalletFormSchema>>;
```

- Schema là **hàm** vì độ chính xác phụ thuộc currency đang chọn (plan §4.5: "Zod schema động"). Component gọi trong `useMemo(() => makeWalletFormSchema(currencies), [currencies])`.
- Không kiểm tra trùng tên phía client (collation, như Categories).
- `Schemas/index.ts` thêm `export * from './wallet.schema';`.
- `lib/money.ts` thêm `amountIssue` (§4.5).

**`Pages/Wallets/WalletFormModal.tsx`**: idiom y hệt `CategoryFormModal.tsx` (TanStack Form + `revalidateLogic()` + `onDynamic`, `useStore` cho `isSubmitting`, `Promise` resolve ở `onFinish`, `serverErrors` + xóa khi gõ, `fieldError`, `form={formId}` trên nút submit, `isCloseDisabled={isSubmitting}`).

```ts
interface WalletFormModalProps {
    /** The wallet being edited; omitted when creating (FE4). */
    wallet?: Wallet;
    currencies: Currency[];
    onClose: () => void;
}
```

| Field             | Control                                                                                              | Mặc định khi Thêm                                                        |
| ----------------- | ---------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| `name`            | `input.form-control.text-dark.h-55`, `maxLength={100}`, `autoFocus`, placeholder `Enter wallet name` | `''`                                                                     |
| `type`            | `select.form-select.form-control.text-dark.h-55`, 5 option từ `WALLET_TYPE_LABELS`                   | `'other'`                                                                |
| `currency_code`   | `select` như trên; option = currency `is_active`, nhãn `` `${code} · ${name}` ``                     | `'VND'` nếu VND đang active, không thì code của currency active đầu tiên |
| `initial_balance` | `input type="text" inputMode="decimal" autoComplete="off"` + dòng xem trước (bên dưới)               | `'0'`                                                                    |
| `description`     | `textarea.form-control.text-dark`, `rows={3}`, `maxLength={255}`, nhãn kèm `(Optional)` mờ           | `''`                                                                     |

- Không icon trong control (rule `frontend-forms.md`), không `FormField`.
- **Dòng xem trước**: `span.d-block.fs-14.text-secondary.mt-1` = `formatMoney(value.trim(), currency, locale)`, **chỉ** hiện khi `currency !== undefined` **và** `amountIssue(value, currency) === null`; có lỗi thì chỉ hiện lỗi. (`amountIssue` trả `null` khi chưa có currency, nên thiếu vế đầu thì `formatMoney` nhận `undefined`.)
- Không cần validate lại thủ công khi đổi currency: `revalidateLogic()` không chạy schema trước lần submit đầu, và từ sau đó chạy lại **toàn bộ** schema ở mỗi thay đổi, nên đổi currency tự cập nhật lỗi của `initial_balance` (khuôn `CategoryFormModal`).
- `serverErrors`: `name`, `type`, `currency_code`, `initial_balance`, `description`, `status` (`status` dùng ở FE4 → alert đầu modal).
- Submit: `router.post('/wallets', value, { ...IN_PLACE_SUBMIT, onSuccess: onClose, onError: setServerErrors, onFinish: resolve })`.
- Title `Add New Wallet`; nút `Create` / `Creating…`; nút `Cancel` (`btn btn-danger text-white`).
- `Index.tsx`: nút "Add New Wallet" → `{ kind: 'create' }` → `<WalletFormModal currencies={currencies} onClose={closeModal} />`.

**Test tay**

- [ ] Mở modal: Type = Other, Currency = VND, Initial balance = 0, xem trước `0 ₫`.
- [ ] Select Currency chỉ có currency active (tắt `USD` qua tinker → không còn trong list).
- [ ] Để trống tên, bấm Create → lỗi Zod, không gửi request.
- [ ] Gõ `1.000.000` → **không** có dòng xem trước; bấm Create → lỗi "Use digits only…". Sửa thành `1000000` → lỗi biến mất ngay, xem trước `1.000.000 ₫`.
- [ ] Sau lần bấm Create đầu tiên: gõ `10.5` với VND → "This currency does not use decimals."; đổi sang USD → lỗi biến mất, xem trước `10,50 $`; đổi lại VND → lỗi hiện lại **ngay**.
- [ ] `10.505` với USD → "Too many decimal places…".
- [ ] 13 chữ số → "The amount is too large."; `999999999999.50` với USD → cũng "too large"; `999999999999.00` với USD → hợp lệ.
- [ ] Tạo "Tiền mặt" (Cash, VND, 500000) → modal đóng, toast `Đã tạo ví.`, thẻ mới ở **cuối** lưới (`created_at ASC`).
- [ ] Tạo "tien mat" khi đã có "Tiền mặt" → modal giữ mở, lỗi server dưới Name; gõ lại → lỗi biến mất.
- [ ] Đóng rồi mở lại → form về mặc định, không còn lỗi cũ. Click backdrop / Esc → **không** đóng.

### FE4 — Modal Sửa ví

**Commit:** `feat(wallets): edit wallet form`
**Phụ thuộc:** FE3, backend T4

- `lib/money.ts` thêm `toAmountInput` (§4.5).
- `WalletFormModal` có `wallet` → chế độ Sửa:
    - `defaultValues`: `name`, `type`, `currency_code` của ví; `initial_balance: toAmountInput(wallet.initial_balance, dp)` với `dp` của currency ví đang dùng; `description: wallet.description ?? ''`.
    - Title `Edit Wallet`; nút `Save Changes` / `Saving…`.
    - Submit: `router.patch(`/wallets/${wallet.id}`, value, options)`, **luôn gửi đủ 5 field**.
- **Khóa** khi `wallet.transactions_count > 0` (plan §4.3): `disabled` trên select Currency và ô Initial balance; hint `span.d-block.fs-14.text-secondary.mt-1` dưới mỗi ô (`Currency is locked…`, `Initial balance is locked…`). Giá trị vẫn trong form state nên vẫn gửi đi. Ô bị khóa **ẩn** dòng xem trước để không lặp với hint.
- **Option Currency** khi Sửa: currency active **cộng** currency hiện tại của ví nếu nó đã tắt, nhãn `t(':currency (inactive)', { currency: `${code} · ${name}` })`.
- `serverErrors.status` (ví bị archive ở tab khác) → `alert alert-danger` đầu modal body, như `CategoryFormModal`.
- `Index.tsx`: render `<WalletFormModal wallet={modal.wallet} currencies={currencies} onClose={closeModal} />` khi `kind === 'edit'` (thẻ đã gọi `onEdit` từ FE2).

**Test tay**

- [ ] Ví chưa có giao dịch: đổi tên, loại, currency, số dư, mô tả → toast `Đã cập nhật ví.`, thẻ cập nhật.
- [ ] Ví VND `1000000.0000` → ô hiện `1000000`; ví USD `10.5000` → `10.50`.
- [ ] Ví có giao dịch: Currency + Initial balance bị khóa kèm hint; đổi tên → thành công.
- [ ] Ví đang dùng USD rồi tắt USD (tinker) → mở Sửa: select có `USD · US Dollar (ngừng hỗ trợ)`, lưu được khi giữ nguyên.
- [ ] Ví archived: mục Edit mờ, không mở được modal.
- [ ] Mở modal Sửa, archive ví đó ở tab khác, rồi lưu → alert lỗi `status` ở đầu modal, modal giữ mở.
- [ ] Submit không đổi gì → thành công.

### FE5 — Modal đổi trạng thái

**Commit:** `feat(wallets): change wallet status`
**Phụ thuộc:** FE2, backend T5

- `Pages/Wallets/WalletStatusModal.tsx`: chép `CategoryStatusModal.tsx` (đã dùng `ENTITY_STATUS_*` sau FE1), đổi prop thành `wallet: Wallet`, endpoint `/wallets/{id}/status`. **Không** dùng lại `updateCategoryStatusSchema` (comment của nó gắn với `UpdateCategoryStatusRequest`); khai báo `updateWalletStatusSchema` trong `wallet.schema.ts`, cùng shape, comment nêu `UpdateWalletStatusRequest`.
- Title `Change Status` (key có sẵn); nút `Save` disabled khi giá trị bằng trạng thái hiện tại hoặc đang submit.
- `Index.tsx` render modal khi `kind === 'status'` (thẻ đã gọi `onChangeStatus` từ FE2).

**Test tay**

- [ ] Active → Inactive: pill đổi màu, thẻ vẫn hiện, toast `Đã cập nhật trạng thái ví.`.
- [ ] Active → Archived khi "Show archived" tắt → thẻ biến mất; bật checkbox → thấy lại, pill Archived.
- [ ] Archived → Active → mục Edit bật lại.
- [ ] Archive ví active **duy nhất** → thành công (backend Q7).
- [ ] Ví có giao dịch vẫn đổi trạng thái được.

### FE6 — Modal xóa ví

**Commit:** `feat(wallets): delete wallet`
**Phụ thuộc:** FE2, backend T6

- `Pages/Wallets/DeleteWalletModal.tsx`: chép `DeleteCategoryModal.tsx`, đổi prop `wallet`, endpoint `/wallets/{id}`, title `Delete Wallet`. Đóng ở **`onFinish`** (xóa bị chặn vẫn là redirect thành công).
- `Index.tsx` render modal khi `kind === 'delete'` (thẻ đã gọi `onDelete` từ FE2).
- `docs/README.md`: cập nhật trạng thái hai dòng Wallets (backend + frontend).

**Test tay**

- [ ] Ví chưa có giao dịch → xác nhận → thẻ biến mất, toast `Đã xóa ví.`.
- [ ] Ví có giao dịch → mục Delete mờ.
- [ ] Mô phỏng race (mở modal xóa, tạo giao dịch cho ví đó qua tinker, bấm Delete) → modal đóng, **toast lỗi**, thẻ vẫn còn.
- [ ] Ví archived chưa có giao dịch → xóa được (khi "Show archived" bật).
- [ ] Xóa khi đang `?include_archived=1` → URL giữ nguyên query.

---

## 8. Checklist chung (sau FE6)

- [ ] Toàn bộ trang ở **English**: không còn chuỗi tiếng Việt cứng; ở **Tiếng Việt**: không còn key tiếng Anh lọt ra.
- [ ] Hai mutation liên tiếp cùng loại → hai toast; mở/đóng modal, đổi filter → không toast lại.
- [ ] Mobile: toolbar wrap gọn, thẻ 1 cột, menu ⋯ không tràn ra ngoài màn hình, modal vừa màn hình.
- [ ] Bàn phím: Tab tới checkbox, nút Add, nút ⋯ của từng thẻ, các mục menu, các field trong modal.
- [ ] Categories vẫn hoạt động y như trước FE1.

---

## 9. Đã cân nhắc và cố ý bỏ

| Bỏ qua                                                       | Lý do                                                                                                             |
| ------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------- |
| Bảng (như Categories)                                        | Người dùng chọn lưới thẻ theo `teams.html`. (Q2)                                                                  |
| Hàng `ActionButton` trên thẻ, tách `ActionButton` dùng chung | Thao tác đi qua menu ⋯ theo template; Categories giữ nút của mình. (Q8)                                           |
| `StatusModal` / `ConfirmDeleteModal` generic                 | Mỗi modal ~80 dòng, chứa URL và câu chữ riêng; generic thêm nhiều prop mà không bớt logic. (Q3)                   |
| Nút "View Details" / trang chi tiết ví                       | Chưa có màn Transactions; thêm "Xem giao dịch" ở task đó. (Q9)                                                    |
| Phân trang của template                                      | Backend không phân trang; mỗi user có vài ví.                                                                     |
| Dòng tổng số dư trên trang Wallets                           | Frontend không được cộng tiền; tổng theo currency là việc của Dashboard (plan §5).                                |
| `currencies.symbol` từ DB                                    | `narrowSymbol` của Intl tự đặt ký hiệu đúng vị trí theo ngôn ngữ; mã currency hiện kèm để phân biệt `$`/`¥`. (Q4) |
| Chèn dấu phân nhóm khi gõ số tiền                            | Phải xử lý con trỏ/dán/xóa; dòng xem trước đã đủ để không nhầm số 0. (Q5)                                         |
| Message Zod dùng chung key với `MoneyPrecision`              | `fieldError()` dịch không có replacements; message frontend không placeholder. (§4.5)                             |
| Kiểm tra trùng tên phía client                               | Collation không phân biệt hoa/thường và dấu; frontend không tái hiện chính xác được.                              |
| Zustand cho state modal / menu                               | Chỉ một trang dùng → `useState` cục bộ (rule `frontend.md`).                                                      |
| Commit `teams.html`                                          | Chỉ là tài liệu tham khảo, phần lớn là header/sidebar demo. (Q16)                                                 |

---

## 10. Follow-up (ngoài phạm vi)

- Nút "Xem giao dịch" ở cuối thẻ → `/transactions?wallet_id=…` (task Transactions). (Q9)
- Transactions dùng lại `formatMoney`, `toAmountInput`, `amountIssue`, `WALLET_TYPE_*`. (Q14)
- Dashboard dùng `formatMoney` cho tổng theo currency.
- Hoist `'income' | 'expense'` thành `TransactionType` dùng chung khi Transactions cần.
