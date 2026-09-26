# Task — Multi-language (Frontend)

> Phase: **1**.
> Nguồn: [`features/phase-1.md`](../../features/phase-1.md) §6 + [`phases/phase-1.md`](../../phases/phase-1.md) §9 + [`backend.md`](backend.md) + template `multi-lang.html` + buổi grill Q1–Q26.
> Phạm vi: **frontend** — hook `t()`, `LanguageSwitcher`, dịch mọi chuỗi UI, format ngày/số theo ngôn ngữ, `<html lang>`. Cần backend §5.1–§5.6 xong trước (props `locale`, `translations`, route `PUT /locale`).
> Nhánh: `feat/implement-multi-lang` — **một PR chung** với backend (Q22).
>
> **Trạng thái: ✅ đã triển khai** (chưa commit). `tsc --noEmit`, `npm run build`, `npm run lang:check` (132/132 key), Prettier và `php artisan test` (142/142) đều pass; luồng đổi ngôn ngữ đã kiểm tra end-to-end qua HTTP trên `finvo.test`. Checklist tay §7 trên trình duyệt **chưa chạy**.
>
> Khác với đặc tả ban đầu:
>
> - `LoadingOverlay` chuyển vào **trong** render-prop của `<App>` ở `app.tsx` (trước đó nằm ngoài, không đọc được `usePage()` để dịch "Loading..."); vẫn không có `key` nên không remount khi điều hướng.
> - `AuthLayout` bọc `LanguageSwitcher` trong `.right-header-content > ul > li.header-right-item` để dùng lại CSS dropdown của header template.
> - Câu điều khoản ở trang Register dịch theo từng đoạn quanh hai link (`By registering, you agree to our` …); "Already have an account." sửa thành câu hỏi.
> - Dashboard (trang placeholder) bỏ mục breadcrumb "Extra Pages" của template.

---

## 0. Tình trạng hiện tại

| Thành phần                                 | Trạng thái                                                                                             |
| ------------------------------------------ | ------------------------------------------------------------------------------------------------------ |
| Thư viện i18n                              | ❌ không có, và **không thêm** (Q3)                                                                    |
| `multi-lang.html`                          | markup dropdown Trezo: icon `translate`, cờ `wh-30 rounded-circle`, `data-bs-toggle`, `data-simplebar` |
| Cờ trong `public/assets/trezo/images/`     | có `usa.svg`; **không có** cờ Việt Nam                                                                 |
| Header: dropdown notifications / profile   | tự làm bằng React state + click-outside (không dùng Bootstrap JS)                                      |
| `lib/categoryLabels.ts`                    | map nhãn tiếng Anh — "sẵn cho i18n" (Categories FE Q1)                                                 |
| Schema Zod (`auth`, `account`, `category`) | message tiếng Anh là hằng số cấp module                                                                |
| `lib/fieldError.ts`                        | 6 file gọi (Login, Register, Profile, ChangePassword, CategoryForm, CategoryStatus)                    |
| `lib/formatDate.ts`                        | cứng `vi-VN`; chỉ `ProfileForm` gọi                                                                    |
| `<html lang>`                              | Blade render lần đầu; visit Inertia **không** cập nhật                                                 |
| Framework test frontend                    | ❌ không có — kiểm chứng bằng `tsc`, `build`, `lang:check`, checklist tay                              |

---

## 1. Bảng quyết định (grill Q1–Q26, phần frontend)

| #   | Quyết định                                                                                                                                                                       |
| --- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Q3  | Không package i18n. Hook tự viết đọc prop `translations` + `locale`.                                                                                                             |
| Q4  | Dịch: chuỗi UI (13 page, sidebar, header, breadcrumb, `<Head title>`, aria-label), Zod message, nhãn enum, format ngày/số. **Không** dịch các mục demo của template (sẽ bị xóa). |
| Q5  | Nút đổi ngôn ngữ ở Header và trang Login/Register.                                                                                                                               |
| Q7  | Key là câu tiếng Anh. `t('Add New Category')`; thiếu bản dịch → hiện chính key.                                                                                                  |
| Q9  | Đổi ngôn ngữ = `router.put('/locale', { locale })` **không** `IN_PLACE_SUBMIT` → hiện Preloader.                                                                                 |
| Q12 | Dịch ở **lớp hiển thị**: schema Zod, `categoryLabels.ts`, `sidebarMenu.ts` giữ câu tiếng Anh; component gọi `t()`. `fieldError` dịch lỗi Zod, **không** dịch lỗi server.         |
| Q14 | Cờ tròn: thêm `vietnam.svg`; English dùng `usa.svg`. Nút là icon `translate`. Mục đang chọn có dấu ✓.                                                                            |
| Q15 | `vi` → `vi-VN`; `en` → `en-GB`. Timezone `Asia/Ho_Chi_Minh`.                                                                                                                     |
| Q18 | `useTranslation()` → `{ t, locale }`; `t(key, replacements?)` thay `:name`. Không số nhiều.                                                                                      |
| Q19 | Chuỗi không nằm trực tiếp trong `t('…')` (schema, map nhãn, config) được đánh dấu bằng `trans('…')` (hàm identity) để `lang:check` thấy.                                         |
| Q20 | `Components/Common/LanguageSwitcher.tsx`: React state + click-outside giống dropdown Header; dùng ở Header và `AuthLayout` (góc phải, ngang logo).                               |
| Q21 | Sửa chữ "Trezo" trên trang auth thành Finvo trước khi dịch.                                                                                                                      |
| Q25 | Thuật ngữ theo plan §9.2; tên ngôn ngữ luôn là "Tiếng Việt" / "English".                                                                                                         |
| Q26 | `document.documentElement.lang` cập nhật sau mỗi visit thành công.                                                                                                               |

Quyết định cũ bị thay thế: `tasks/categories/frontend.md` Q1 ("UI tiếng Anh") và follow-up i18n ở §10; `tasks/accounts/frontend.md` D13.

---

## 2. Danh sách file

**Tạo mới**

```
resources/js/types/locale.types.ts
resources/js/lib/i18n.ts
resources/js/hooks/useTranslation.ts
resources/js/Components/Common/LanguageSwitcher.tsx
public/assets/trezo/images/vietnam.svg
```

**Sửa**

```
resources/js/types/index.d.ts                    (+ re-export locale.types; PageProps + locale, translations)
resources/js/app.tsx                             (<html lang> sau mỗi visit)
resources/js/lib/fieldError.ts                   (+ tham số t)                      ⚠️ impact analysis
resources/js/lib/formatDate.ts                   (+ tham số locale)                 ⚠️ impact analysis
resources/js/lib/categoryLabels.ts               (trans() cho mọi nhãn/mô tả)
resources/js/Config/sidebarMenu.ts               (trans() cho mục của Finvo)
resources/js/Schemas/auth.schema.ts              (trans() cho message)
resources/js/Schemas/account.schema.ts
resources/js/Schemas/category.schema.ts
resources/js/Components/Layout/Header.tsx        (+ LanguageSwitcher, dịch Account/Logout/aria)
resources/js/Components/Layout/Sidebar.tsx       (t(group.title), t(item.title), aria)
resources/js/Components/Auth/AuthLayout.tsx      (+ LanguageSwitcher, alt)
resources/js/Components/Common/Modal.tsx         (aria-label "Close")
resources/js/Components/Common/LoadingOverlay.tsx ("Loading...")
resources/js/Components/Form/PasswordInput.tsx   (aria-label Show/Hide password)
resources/js/Pages/**/*.tsx                      (13 file — mọi chuỗi UI)
lang/vi.json                                     (phần chuỗi frontend)
```

**Không đụng tới:** `FlashToasts.tsx` (flash đã dịch ở backend), `Preloader.tsx` (chữ "FINVO" là logo), `Footer.tsx` (chỉ có tên sản phẩm), `inPlaceSubmit.ts`, các mục demo trong `Header.tsx` / `sidebarMenu.ts` / default của `Breadcrumb.tsx`.

---

## 3. Contract với backend (tham chiếu)

| Nguồn                               | Nội dung                                                                                                       |
| ----------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| prop `locale`                       | `'vi' \| 'en'`, có ở mọi page (kể cả Login/Register)                                                           |
| prop `translations`                 | `Record<string, string>`: câu tiếng Anh → câu tiếng Việt; `{}` khi `en`. Once prop — client giữ qua các visit. |
| `flash.status`, `flash.error`       | đã dịch sẵn                                                                                                    |
| `errors.*` (validation)             | đã dịch sẵn                                                                                                    |
| `router.put('/locale', { locale })` | redirect back + cookie; lỗi `errors.locale` chỉ xảy ra nếu gửi sai giá trị                                     |

---

## 4. Đặc tả từng file

### 4.1. `resources/js/types/locale.types.ts` + `lib/i18n.ts`

```ts
// types/locale.types.ts
/** Mirrors App\Enums\Locale. */
export type Locale = 'vi' | 'en';

/** Flat dictionary from lang/vi.json: English source string → translation. */
export type Translations = Record<string, string>;
```

`types/index.d.ts`: `export * from './locale.types';` và `PageProps` thêm `locale: Locale; translations: Translations;`.

```ts
// lib/i18n.ts
export type Replacements = Record<string, string | number>;

/** Languages offered by LanguageSwitcher, each named in its own language (Q25). */
export const LOCALES: ReadonlyArray<{ code: Locale; name: string; flag: string }> = [
    { code: 'vi', name: 'Tiếng Việt', flag: '/assets/trezo/images/vietnam.svg' },
    { code: 'en', name: 'English', flag: '/assets/trezo/images/usa.svg' },
];

/** Intl locale used to format dates and numbers for each UI language (Q15). */
export const INTL_LOCALES: Record<Locale, string> = { vi: 'vi-VN', en: 'en-GB' };

/**
 * Marks an English source string for `npm run lang:check` without translating it.
 * Used where t() cannot run: Zod schemas, label maps and menu config at module level.
 */
export function trans(key: string): string {
    return key;
}

/**
 * Look the key up and fill Laravel-style `:name` placeholders.
 * A missing key falls back to the English key itself.
 */
export function translate(translations: Translations, key: string, replacements?: Replacements): string;
```

- Thay placeholder theo thứ tự **tên dài trước** để `:name` không ăn vào `:names`.
- Không hỗ trợ `:Name` / `:NAME` hay số nhiều (Q18).

### 4.2. `resources/js/hooks/useTranslation.ts`

```ts
export function useTranslation(): { t: (key: string, replacements?: Replacements) => string; locale: Locale } {
    const { translations, locale } = usePage<PageProps>().props;
    const t = useCallback((key, replacements) => translate(translations, key, replacements), [translations]);
    return { t, locale };
}
```

- Thư mục `resources/js/hooks/` mới — đúng quy ước `hooks/` + `useXxx.ts` trong `.claude/rules/frontend.md`.
- Chỉ dùng trong component. Code ngoài component (schema, config) dùng `trans()` và để component dịch.

### 4.3. `resources/js/lib/fieldError.ts` ⚠️

```ts
export function fieldError(t: TranslateFn, errors: unknown[], serverError?: string): string | undefined;
```

- Lỗi Zod (message là key tiếng Anh) → `t(message)`.
- Lỗi server → trả **nguyên văn** (backend đã dịch).
- `t` đứng đầu vì `serverError` là tham số tùy chọn. Sửa cả 6 caller.

Trước khi sửa: `node .gitnexus/run.cjs impact "fieldError" --direction upstream --repo .`.

### 4.4. `resources/js/lib/formatDate.ts` ⚠️

```ts
export function formatDate(iso: string, locale: Locale): string;
```

`Intl.DateTimeFormat(INTL_LOCALES[locale], { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'Asia/Ho_Chi_Minh' })`, cache một formatter mỗi locale. Cả hai ra `dd/MM/yyyy`. Caller: `ProfileForm` (`formatDate(account.created_at, locale)`).

Tiền tệ chưa có helper (Wallets/Transactions sẽ tạo) → ghi chú trong follow-up: helper tiền tệ phải nhận `locale` và dùng `INTL_LOCALES`.

Impact analysis `formatDate` trước khi sửa.

### 4.5. Schema Zod, `categoryLabels.ts`, `sidebarMenu.ts`

Bọc **mọi** chuỗi hiển thị bằng `trans()`; giá trị không đổi:

```ts
name: z.string().trim().min(1, trans('Category name is required')).max(100, trans('Category name must not exceed 100 characters')),

export const CATEGORY_TYPE_LABELS: Record<CategoryType, string> = { income: trans('Income'), expense: trans('Expense') };

{ id: 'categories', title: trans('Categories'), icon: 'category', url: '/categories' },
```

- `sidebarMenu.ts`: chỉ bọc mục của Finvo (`MAIN`, `OTHERS`, `Categories`, `Account`). Mục demo (`Front Pages`, `Google Map`…) **không** bọc → không vào `vi.json`, `t()` trả nguyên văn (Q4).
- Component hiển thị: `t(CATEGORY_TYPE_LABELS[type])`, `t(group.title)`, `t(item.title)`.

### 4.6. `resources/js/Components/Common/LanguageSwitcher.tsx`

Markup theo `multi-lang.html`, cơ chế dropdown theo Header (Q20):

- Nút: `btn btn-secondary dropdown-toggle border-0 p-0 position-relative` + `<span className="material-symbols-outlined">translate</span>`, `aria-label={t('Choose Language')}`, `aria-expanded`.
- Menu `dropdown-menu dropdown-lg p-0 border-0 dropdown-menu-end` (+ `show` khi mở), tiêu đề `t('Choose Language')`, mỗi mục là `<button type="button" className="dropdown-item">` với cờ `wh-30 rounded-circle` + tên từ `LOCALES`.
- **Bỏ** `data-bs-toggle` và `data-simplebar` (chỉ 2 mục).
- Mục đang chọn: `aria-current="true"` + icon ✓ (`material-symbols-outlined` `check`) bên phải; bấm vào mục đang chọn chỉ đóng menu, không gửi request.
- Chọn mục khác → đóng menu → `router.put('/locale', { locale: code })` (không `IN_PLACE_SUBMIT` → Preloader, Q9).
- Click ra ngoài / Esc → đóng.
- Prop `className?: string` để Header bọc trong `<li className="header-right-item">` và AuthLayout tự đặt vị trí.

### 4.7. `public/assets/trezo/images/vietnam.svg`

Cờ tròn tự vẽ, cùng khung với `usa.svg` (viewBox vuông, hiển thị `wh-30 rounded-circle`): nền đỏ `#DA251D`, sao vàng năm cánh `#FFFF00` ở giữa. Không lấy từ nguồn có bản quyền.

### 4.8. `Header.tsx`, `Sidebar.tsx`, `AuthLayout.tsx`

- `Header.tsx`: thêm `<li className="header-right-item"><LanguageSwitcher /></li>` **trước** mục notifications. Dịch "Account", "Logout", `aria-label="Toggle Navigation"`. Không dịch notifications demo, "Messages", "Billing", "Support", tên/chức danh fallback — thuộc task dọn dẹp template.
- `Sidebar.tsx`: `t(group.title)`, `t(item.title)`, `aria-label` "Toggle Sidebar".
- `AuthLayout.tsx`: hàng logo thành `d-flex justify-content-between align-items-center mb-4`, logo bên trái, `LanguageSwitcher` bên phải. `alt` dịch.

### 4.9. `app.tsx` — `<html lang>` (Q26)

```ts
// Blade only renders <html lang> on the first load; keep it in step after Inertia visits.
router.on('success', (event) => {
    document.documentElement.lang = (event.detail.page.props as PageProps).locale;
});
```

Đăng ký một lần trong `setup`, cạnh việc mount `Preloader` / `LoadingOverlay` / `Toaster`.

### 4.10. Dịch các page

Quy tắc chung cho 13 file trong `Pages/`:

- `const { t, locale } = useTranslation();` ở đầu component.
- Mọi text node, `placeholder`, `title`, `aria-label`, `alt`, nhãn nút, trạng thái nút (`"Saving…"`), `<Head title>`, `Breadcrumb` (`title` + `items[].label`) đi qua `t()`.
- Câu có biến dùng placeholder, **không** nối chuỗi: `t('Delete ":name"? This cannot be undone.', { name: category.name })`, `t('Edit :name', { name })`.
    - `DeleteCategoryModal` đang in đậm tên (`<strong>`) giữa câu: bỏ `<strong>`, dùng một câu có placeholder trong dấu ngoặc kép. Không tự viết cơ chế nhúng JSX vào bản dịch cho một call site.
- Lỗi field: `fieldError(t, field.state.meta.errors, serverErrors.x)`.

Riêng từng trang:

| File                                               | Ghi chú                                                                                                                                                                 |
| -------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Login/Index.tsx`                                  | "Welcome back to Trezo!" → `t('Welcome back to Finvo!')` (Q21).                                                                                                         |
| `Login/LoginForm.tsx`, `Register/RegisterForm.tsx` | placeholder `example@trezo.com` → `example@finvo.com` (không dịch, là email mẫu).                                                                                       |
| `Register/Index.tsx`                               | "Register to Finvo Dashboard"… dịch bình thường.                                                                                                                        |
| `Dashboard/Index.tsx`                              | Trang placeholder: dịch `title` và breadcrumb "Dashboard" → "Tổng quan"; bỏ mục "Extra Pages" của template; phần "Content Area" giữ nguyên (sẽ thay khi làm Dashboard). |
| `Account/ProfileForm.tsx`                          | `formatDate(account.created_at, locale)`.                                                                                                                               |
| `Categories/*`                                     | `t(CATEGORY_*_LABELS[…])`, `t(CATEGORY_STATUS_DESCRIPTIONS[…])`; title nút disabled ("Restore this category before editing."…) qua `t()`.                               |

### 4.11. `lang/vi.json` (phần frontend)

Thêm mọi key frontend, theo thuật ngữ plan §9.2. `npm run lang:check` phải pass (không key thiếu). Ví dụ:

```json
{
    "Add New Category": "Thêm danh mục",
    "Choose Language": "Chọn ngôn ngữ",
    "Income": "Thu",
    "Show archived": "Hiện mục đã lưu trữ",
    "Welcome back to Finvo!": "Chào mừng bạn quay lại Finvo!"
}
```

---

## 5. Idiom bắt buộc

- Không nối chuỗi để tạo câu; dùng placeholder `:name` (thứ tự từ khác nhau giữa hai ngôn ngữ).
- Chuỗi ở cấp module → `trans()`; trong component → `t()`. Không gọi `t()` ngoài component.
- Không dịch lại chuỗi đến từ server (`flash`, `errors`).
- Không dịch dữ liệu người dùng (tên danh mục, tên ví).
- Dấu câu và dấu `…` là một phần của key: `"Saving…"` ≠ `"Saving..."`.
- Comment trong code vẫn bằng tiếng Anh.

---

## 6. Trình tự triển khai

1. `types/locale.types.ts` + `PageProps` → `lib/i18n.ts` → `hooks/useTranslation.ts`.
2. Impact analysis → `fieldError` (+ 6 caller) → `formatDate` (+ `ProfileForm`).
3. `trans()` trong schema, `categoryLabels.ts`, `sidebarMenu.ts`.
4. `vietnam.svg` → `LanguageSwitcher` → gắn vào `Header`, `AuthLayout`.
5. `app.tsx` (`<html lang>`).
6. Dịch lần lượt: Layout (Header, Sidebar, Modal, LoadingOverlay, PasswordInput) → Login/Register → Account → Categories → Dashboard.
7. Điền `lang/vi.json` cho đến khi `npm run lang:check` pass.
8. `npx tsc --noEmit` → `npm run format` → `npm run format:check` → `npm run build`.
9. Checklist §7 trên trình duyệt.
10. Trước commit: `node .gitnexus/run.cjs detect-changes --scope all --repo .` (không chấp nhận `partial` / `truncated`).

---

## 7. Checklist test tay

### Chuyển ngôn ngữ

- [ ] Xóa cookie → mở `/login`: giao diện Tiếng Việt, `<html lang="vi">`.
- [ ] Trang Login có dropdown góc phải ngang logo; chọn English → Preloader, toàn trang English, `<html lang="en">`.
- [ ] Reload → vẫn English. Đăng nhập → vẫn English. Đăng xuất → trang Login vẫn English.
- [ ] Header có icon `translate` trước chuông thông báo; menu có "Tiếng Việt" (cờ VN) và "English" (cờ Mỹ), mục đang chọn có ✓.
- [ ] Bấm mục đang chọn → menu đóng, không có request.
- [ ] Click ra ngoài / Esc → menu đóng.
- [ ] Đổi ngôn ngữ ở trang Categories đang có filter `?type=income&include_archived=1` → sau khi đổi vẫn giữ URL và filter.
- [ ] Đổi vi → en → vi: lần về `vi` không tải lại từ điển (DevTools: response không có `translations`).

### Nội dung

- [ ] Tiếng Việt: sidebar "Danh mục", "Tài khoản"; tab "Tất cả / Thu / Chi"; badge "Đang dùng / Tạm ngưng / Đã lưu trữ".
- [ ] Mục demo của template (Front Pages, Google Map, Messages, Billing…) vẫn là tiếng Anh — đúng thiết kế.
- [ ] Lỗi Zod (để trống tên danh mục) hiện tiếng Việt; lỗi server (tên trùng) cũng tiếng Việt.
- [ ] Toast sau thêm/sửa/xóa danh mục và alert trang Account/Login hiện tiếng Việt.
- [ ] Modal xóa: "Xóa "Cafe"? …" có tên danh mục đúng chỗ ở cả hai ngôn ngữ.
- [ ] Thông báo throttle (đăng nhập sai nhiều lần) hiện tiếng Việt có số giây.
- [ ] Ngày đăng ký ở trang Account là `dd/MM/yyyy` ở cả hai ngôn ngữ.
- [ ] Tab trình duyệt: `<title>` dịch đúng ("Danh mục | Finvo").
- [ ] Trang Login: "Chào mừng bạn quay lại Finvo!", không còn chữ "Trezo".

### Dữ liệu seed

- [ ] Chọn English ở trang Register → đăng ký → Categories có "Salary", "Food & Drinks"…; chuyển sang Tiếng Việt → tên danh mục **không** đổi.
- [ ] Đăng ký ở Tiếng Việt → "Lương", "Ăn uống"…

### Chung

- [ ] Mobile: dropdown ngôn ngữ trên Header và trang auth không tràn màn hình.
- [ ] Bàn phím: Tab tới nút ngôn ngữ, Enter mở, Tab qua các mục.
- [ ] Không có key tiếng Anh sót lại khi ở Tiếng Việt (ngoài mục demo) — rà từng trang.

---

## 8. Đã cân nhắc và cố ý bỏ

| Bỏ qua                                      | Lý do                                                           |
| ------------------------------------------- | --------------------------------------------------------------- |
| react-i18next, `laravel-react-i18n`         | Hai nguồn bản dịch / thêm dependency (Q3).                      |
| Schema factory `makeXSchema(t)`             | Phải sửa mọi schema và form; dịch ở lớp hiển thị đủ dùng (Q12). |
| Số nhiều                                    | Chưa có call site (Q18).                                        |
| Bootstrap `data-bs-toggle` / `simplebar`    | Không đồng bộ với các dropdown React khác; chỉ 2 mục (Q20).     |
| `window.location.reload()` khi đổi ngôn ngữ | Mất state; cập nhật `<html lang>` bằng JS là đủ (Q26).          |
| `LoadingOverlay` khi đổi ngôn ngữ           | Toàn bộ chữ đổi, cảm giác như trang mới → Preloader (Q9).       |
| Dịch mục demo của template                  | Sẽ bị xóa ở task riêng (Q4, Q23).                               |
| Mục chọn ngôn ngữ trong trang Account       | Dropdown Header luôn có sẵn (Q5).                               |

---

## 9. Follow-up

- **Task dọn dẹp template** (Q23): xóa mục demo ở `sidebarMenu.ts`, notifications / "Messages" / "Billing" / "Support" / fallback "Olivia John" ở `Header.tsx`, default "Extra Pages" của `Breadcrumb.tsx`, `SocialButtons`, link "Forgot Password?" (xem `tasks/accounts/frontend.md` §9).
- Helper format tiền tệ (Wallets/Transactions) phải nhận `locale` và dùng `INTL_LOCALES`.
- Chuyển alert flash ở Account/Login sang toast (đã có từ `tasks/accounts/frontend.md` §9) — độc lập với task này.
