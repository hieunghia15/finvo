# Task — Accounts (Frontend)

> Phase: **1**.
> Nguồn: [`features/phase-1.md`](../../features/phase-1.md) §5 + [`phases/phase-1.md`](../../phases/phase-1.md) §4.7 + [`tasks/accounts/backend.md`](backend.md).
> Phạm vi: **chỉ frontend** — trang Login, Register, Account (2 tab: Account / Change Password), nút Logout, và hạ tầng loading/validation dùng chung ra đời cùng module này.
> Tài liệu này được viết **sau khi triển khai** (as-built), rút từ code hiện tại.

---

## 0. Tình trạng hiện tại

| Thành phần                                                              | PR / commit                          | Trạng thái |
| ----------------------------------------------------------------------- | ------------------------------------ | ---------- |
| Trang Login, Register (`AuthLayout`, `SocialButtons`)                   | #3 (`feat/create-login-page`), #4    | ✅         |
| Bỏ axios + auth context, chuyển sang Inertia                            | `a8759b3`                            | ✅         |
| Zod `auth.schema.ts` khớp `ValidEmail` / `ValidPassword`                | #9, #10                              | ✅         |
| Trang Account: `ProfileForm`, `ChangePasswordForm`, `account.schema.ts` | #17 (`feat/implement-user-frontend`) | ✅         |
| `LoadingOverlay` + `IN_PLACE_SUBMIT` / `isInPlaceSubmit()`              | `6b0eb86` (#17)                      | ✅         |
| `FormField`, `PasswordInput`, `fieldError()`, `formatDate()`            | `972abc3` (#17)                      | ✅         |
| Flash `status` hiện bằng **alert inline** (chưa chuyển sang toast)      | –                                    | ⚠️ §9      |

---

## 1. Bảng quyết định

| #   | Quyết định                                                                                                                                                                          |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| D1  | Form dùng **TanStack Form + Zod** (`validationLogic: revalidateLogic()`, `validators: { onDynamic: schema }`). Không dùng `useForm` của Inertia.                                    |
| D2  | Zod schema **sao y** rule backend: `emailRegex` = `ValidEmail`, `passwordRegex` = `ValidPassword`, `max(255)` cho name/email. Mỗi schema có comment "Must match the backend …".     |
| D3  | Lỗi hiển thị qua `fieldError(zodErrors, serverError)`: lỗi Zod ưu tiên, sau đó mới tới lỗi Laravel. Gõ lại vào field nào thì xóa lỗi server của field đó.                           |
| D4  | Trang Account có **2 tab là state cục bộ** (`useState<'profile' \| 'password'>`), không đổi URL — backend chỉ có một `GET /account`.                                                |
| D5  | Form Account submit bằng `IN_PLACE_SUBMIT` → giữ tab đang mở, giữ scroll, hiện `LoadingOverlay` thay vì Preloader toàn trang. Login / Register / Logout **không** dùng (đổi trang). |
| D6  | `FormField` (label + input + hint + lỗi) **chỉ dùng cho trang Account**. Login / Register viết markup inline; Categories cũng vậy.                                                  |
| D7  | Email và ngày đăng ký hiện **read-only** (`disabled`) trong tab Account; hint "Email cannot be changed.".                                                                           |
| D8  | Sau khi lưu tên: reset form bằng **tên đã được server chuẩn hóa** (`Str::squish`) lấy từ `page.props.account` → nút Cancel quay về giá trị đó.                                      |
| D9  | Sau khi đổi mật khẩu thành công: reset cả 3 ô về rỗng. Ba giá trị chỉ sống trong form state.                                                                                        |
| D10 | Ô mật khẩu ở trang Account dùng `PasswordInput` (nút hiện/ẩn). Login / Register dùng `<input type="password">` thường.                                                              |
| D11 | Ngày hiển thị qua `formatDate()` — `dd/MM/yyyy`, locale `vi-VN`, timezone `Asia/Ho_Chi_Minh`.                                                                                       |
| D12 | Điểm vào trang Account: mục **Account** trong nhóm **OTHERS** của sidebar + mục trong dropdown profile ở Header. Logout ở dropdown Header (`router.post('/logout')`).               |
| D13 | UI **tiếng Anh**.                                                                                                                                                                   |

---

## 2. Danh sách file

```
resources/js/Pages/Login/Index.tsx
resources/js/Pages/Login/LoginForm.tsx
resources/js/Pages/Register/Index.tsx
resources/js/Pages/Register/RegisterForm.tsx
resources/js/Pages/Account/Index.tsx
resources/js/Pages/Account/ProfileForm.tsx
resources/js/Pages/Account/ChangePasswordForm.tsx
resources/js/Components/Auth/AuthLayout.tsx
resources/js/Components/Auth/SocialButtons.tsx
resources/js/Components/Form/FormField.tsx
resources/js/Components/Form/PasswordInput.tsx
resources/js/Components/Common/LoadingOverlay.tsx
resources/js/Components/Common/Preloader.tsx
resources/js/Components/Layout/Header.tsx        (dropdown: Account, Logout)
resources/js/Config/sidebarMenu.ts               (mục "Account" trong OTHERS)
resources/js/Schemas/auth.schema.ts
resources/js/Schemas/account.schema.ts
resources/js/lib/fieldError.ts
resources/js/lib/formatDate.ts
resources/js/lib/inPlaceSubmit.ts
resources/js/types/index.d.ts                    (User, Account, Auth, Flash, PageProps)
```

---

## 3. Contract với backend (tham chiếu)

| Thao tác      | Visit                                                 | Payload                                                 | Kết quả                                                                                 |
| ------------- | ----------------------------------------------------- | ------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| Đăng nhập     | `router.post('/login', …)`                            | `email`, `password`, `remember`                         | redirect `/dashboard` (hoặc URL intended) / lỗi `email`, `password`                     |
| Đăng ký       | `router.post('/register', …)`                         | `name`, `email`, `password`, `password_confirmation`    | redirect `/login` + `flash.status = "Account created. Please log in."` / lỗi theo field |
| Đăng xuất     | `router.post('/logout')`                              | –                                                       | redirect `/login`                                                                       |
| Xem tài khoản | `GET /account`                                        | –                                                       | prop `account: { name, email, created_at }`                                             |
| Đổi tên       | `router.patch('/account', …, IN_PLACE_SUBMIT)`        | `name`                                                  | `flash.status = "Account updated."` / lỗi `name`                                        |
| Đổi mật khẩu  | `router.put('/account/password', …, IN_PLACE_SUBMIT)` | `current_password`, `password`, `password_confirmation` | `flash.status = "Password updated."` / lỗi `current_password`, `password`               |

- Lỗi rate limit trả về như lỗi validation thường: login/register trên `email`, đổi mật khẩu trên `current_password`.
- Lỗi `confirmed` và `different:current_password` của backend nằm trên `password`, không phải `password_confirmation`.
- Sai thông tin đăng nhập báo trên `email` (không tiết lộ field nào sai).

---

## 4. Đặc tả từng file

### 4.1. `resources/js/Schemas/auth.schema.ts`

```ts
const emailRegex = /^([a-zA-Z0-9_.+-])+@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,64})+$/; // = ValidEmail
export const passwordRegex = /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,32}$/; // = ValidPassword
```

- `loginSchema`: `email` (trim, bắt buộc, regex), `password` (chỉ bắt buộc nhập — **không** kiểm tra regex phía client; backend `LoginRequest` vẫn áp `ValidPassword`, nên mật khẩu sai định dạng nhận lỗi server trên field `password`), `remember` (boolean).
- `registerSchema`: `name` (trim, 1–255), `email` (trim, 1–255, regex), `password` (bắt buộc, `passwordRegex`), `password_confirmation` (bắt buộc) + `refine` "Passwords do not match" trên `password_confirmation`.

### 4.2. `resources/js/Schemas/account.schema.ts`

- `updateAccountSchema`: `name` — trim, 1–255 ("Full name is required" / "Full name must not exceed 255 characters").
- `updatePasswordSchema`: `current_password` bắt buộc; `password` bắt buộc + `passwordRegex`; `password_confirmation` bắt buộc; hai `refine`:
    - `password === password_confirmation` → lỗi trên `password_confirmation`.
    - `password !== current_password` → lỗi trên `password` (khớp `different:current_password`).
- Import `passwordRegex` từ `auth.schema.ts` — một nguồn duy nhất.

### 4.3. `resources/js/lib/fieldError.ts`

`fieldError(errors: unknown[], serverError?: string): string | undefined` — trả message của lỗi Zod đầu tiên; nếu schema không có lỗi thì trả lỗi server. Dùng ở **mọi** form (Login, Register, Account, Categories).

### 4.4. `resources/js/lib/inPlaceSubmit.ts`, `LoadingOverlay.tsx`, `Preloader.tsx`

Ba loại chỉ báo loading của app:

| Loại             | Khi nào                                                                  | Quyết định bởi               |
| ---------------- | ------------------------------------------------------------------------ | ---------------------------- |
| `Preloader`      | Lần tải đầu + mọi điều hướng trang (Login, Register, Logout, đổi trang)  | `!isInPlaceSubmit(visit)`    |
| `LoadingOverlay` | Form submit tại chỗ (`IN_PLACE_SUBMIT`) hoặc GET lọc (`IN_PLACE_FILTER`) | `isInPlaceSubmit(visit)`     |
| Nút submit       | `"Saving…"`, `"Changing…"`, `"Signing in…"`, `"Registering…"`            | `isSubmitting` của từng form |

- `IN_PLACE_SUBMIT = { preserveScroll: true, preserveState: true, showProgress: false }`.
- `LoadingOverlay` giữ tối thiểu **300 ms** để không chớp; `z-index: 9998`, ngay dưới Preloader (9999).
- `IN_PLACE_FILTER` và header `X-Finvo-In-Place` được bổ sung sau, ở task Categories frontend §4.4.

### 4.5. `resources/js/Components/Form/FormField.tsx`

```ts
interface FormFieldProps {
    htmlFor: string; // id của input con
    label: string;
    error?: string;
    hint?: string; // ví dụ "Email cannot be changed."
}
```

Render `form-group mb-4` > `label.label.text-secondary` > children > hint > `invalid-feedback d-block`. **Chỉ** dùng trong `Pages/Account/*` (D6).

### 4.6. `resources/js/Components/Form/PasswordInput.tsx`

Input mật khẩu có nút hiện/ẩn (`ri-eye-line` / `ri-eye-off-line`, `aria-label` "Show password" / "Hide password"). Props: `id`, `name`, `value`, `onChange(value)`, `onBlur`, `autoComplete: 'current-password' | 'new-password'`, `placeholder?`, `isInvalid?`. Khi invalid thì tắt icon lỗi của Bootstrap (`backgroundImage: 'none'`) để không đè lên nút toggle.

### 4.7. `resources/js/Pages/Login/*`

- `Index.tsx`: `<Head title="Sign In" />`, `AuthLayout` (ảnh `login.jpg`), `SocialButtons`, `LoginForm`. Hiện `flash.status` bằng `alert alert-success` (thông báo sau khi đăng ký).
- `LoginForm.tsx`: 3 field `email` (`autoComplete="username"`), `password` (`current-password`), `remember` (checkbox "Remember Me"). Link "Forgot Password?" và "Register". Nút "Login" → "Signing in…".
- `router.post('/login', value, { onError, onFinish })` — **không** `IN_PLACE_SUBMIT` (thành công là đổi trang).

### 4.8. `resources/js/Pages/Register/*`

- `Index.tsx`: `<Head title="Register" />`, `AuthLayout` (ảnh `register.jpg`), `SocialButtons`, `RegisterForm`.
- `RegisterForm.tsx`: `name` (`autoComplete="name"`), `email` (`email`), `password`, `password_confirmation` (`new-password`). Nút "Register" → "Registering…". Link "Log In" về `/` (middleware `guest` chuyển tiếp sang `/login`).
- `router.post('/register', value, { onError, onFinish })`.

### 4.9. `resources/js/Pages/Account/Index.tsx`

```ts
type AccountTab = 'profile' | 'password';
type IndexProps = PageProps<{ account: Account }>;
```

- Tiêu đề trang (`<Head>` + `Breadcrumb`) đổi theo tab: `"Account"` / `"Change Password"`; breadcrumb `Dashboard › <title>`.
- `flash.status` → `alert alert-success` phía trên card (xem §9).
- Card `bg-white border-0 rounded-3 mb-4` > 2 nút tab (`btn btn-primary border border-primary py-2 px-3 fw-semibold` + `bg-primary text-white` / `bg-transparent text-primary`, `aria-current="page"` cho tab đang chọn) > `ProfileForm` hoặc `ChangePasswordForm`.
- Class tab này được Categories dùng lại cho filter All / Income / Expense.

### 4.10. `resources/js/Pages/Account/ProfileForm.tsx`

- Tiêu đề "Profile" + "Update your personal details here.".
- Field:
    - **Full Name** — editable, `autoComplete="name"`, icon `ri-user-line` bên trái (`ps-5`).
    - **Email Address** — `disabled`, icon `ri-mail-line`, hint "Email cannot be changed.".
    - **Registered On** — `disabled`, `formatDate(account.created_at)`, icon `ri-calendar-line`.
- Submit: `router.patch('/account', value, { ...IN_PLACE_SUBMIT, onSuccess: (page) => formApi.reset({ name: page.props.account.name }), onError, onFinish })` (D8).
- Nút **Cancel** (`btn btn-danger text-white`): `form.reset()` + xóa lỗi server. Nút **Save Changes** → "Saving…". Cả hai disabled khi đang submit.

### 4.11. `resources/js/Pages/Account/ChangePasswordForm.tsx`

- 3 `PasswordInput`: **Current Password** (`current-password`), **New Password** (`new-password`), **Confirm Password** (`new-password`).
- `serverErrors: { current_password?, password?, password_confirmation? }`; `clearServerError(field)` khi gõ.
- Submit: `router.put('/account/password', value, { ...IN_PLACE_SUBMIT, onSuccess: () => formApi.reset(), onError, onFinish })`.
    - `IN_PLACE_SUBMIT` ở đây là **bắt buộc**: không có nó, redirect về `/account` sẽ remount page và tab rơi về "Account".
- Nút **Change Password** → "Changing…".

### 4.12. `resources/js/types/index.d.ts`

```ts
export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    avatar?: string;
    role?: string;
    created_at?: string;
}
export interface Account {
    name: string;
    email: string;
    created_at: string;
} // ISO 8601, UTC
export interface Auth {
    user: User | null;
}
export interface Flash {
    status?: string | null;
    success?: string;
    error?: string;
    info?: string;
    warning?: string;
}
export type PageProps<T = Record<string, unknown>> = T & { auth: Auth; flash?: Flash; errors: Record<string, string> };
```

Các type này ra đời trước quy ước "mỗi domain một file `types/<domain>.types.ts`"; giữ nguyên vị trí, domain mới (Categories, Wallets…) mới dùng file riêng.

---

## 5. Idiom chung (các form sau phải theo)

- `useStore(form.store, (state) => state.isSubmitting)` — không dùng `<form.Subscribe>`.
- `onSubmit` trả `new Promise<void>` và resolve ở `onFinish`, vì `router.*` không trả promise; nếu không `isSubmitting` sẽ tắt ngay.
- `<form onSubmit={(e) => { e.preventDefault(); e.stopPropagation(); form.handleSubmit(); }}>`.
- `fieldError(field.state.meta.errors, serverErrors.x)` cho mọi field.
- Mutation giữ nguyên trang → `...IN_PLACE_SUBMIT`.
- Comment tiếng Anh, giải thích **vì sao** (mật độ như `ProfileForm.tsx`).

---

## 6. Kiểm chứng

```
npx tsc --noEmit
npm run format:check
npm run build
```

Dự án chưa có framework test frontend → dùng checklist tay ở §7. Trước khi sửa `isInPlaceSubmit`, `fieldError`, `FormField` hoặc `PasswordInput`, chạy impact analysis (`node .gitnexus/run.cjs impact "<symbol>" --direction upstream --repo .`) — các symbol này đã có caller ngoài module Accounts.

---

## 7. Checklist test tay (regression)

### Đăng ký

- [ ] Để trống cả form → lỗi Zod ở từng field, không gửi request.
- [ ] Mật khẩu `abc123` → lỗi regex; xác nhận không khớp → "Passwords do not match" dưới ô xác nhận.
- [ ] Email đã tồn tại (kể cả khác hoa/thường) → lỗi server dưới Email; gõ lại → lỗi biến mất.
- [ ] Đăng ký thành công → Preloader, sang `/login`, alert "Account created. Please log in.".
- [ ] Đăng nhập bằng tài khoản vừa tạo → trang Categories có sẵn 11 danh mục.

### Đăng nhập / đăng xuất

- [ ] Sai mật khẩu → lỗi dưới Email, form giữ nguyên email.
- [ ] Sai 5 lần → thông báo throttle dưới Email.
- [ ] Đăng nhập đúng → `/dashboard`; tick "Remember Me" → đóng trình duyệt mở lại vẫn đăng nhập.
- [ ] Vào `/account` khi chưa đăng nhập → `/login`; đăng nhập xong → quay lại `/account` (intended URL).
- [ ] Header › dropdown › Logout → `/login`; nhấn Back không vào lại được trang cần đăng nhập.

### Tab Account

- [ ] Vào từ sidebar (OTHERS › Account) và từ dropdown Header.
- [ ] Email và Registered On bị disabled; ngày dạng `dd/MM/yyyy`.
- [ ] Xóa hết tên → lỗi Zod; nhập `"  Nguyễn   Văn  A "` → lưu → ô hiện `"Nguyễn Văn A"`, alert "Account updated.".
- [ ] Sửa tên rồi Cancel → quay về tên đã lưu gần nhất.
- [ ] Đang lưu: `LoadingOverlay` hiện, **không** chớp Preloader "FINVO"; nút hiện "Saving…".
- [ ] Tên mới hiện ngay ở Header (shared prop `auth.user` được làm mới).

### Tab Change Password

- [ ] Chuyển tab không đổi URL; tiêu đề/breadcrumb đổi thành "Change Password".
- [ ] Mật khẩu mới trùng mật khẩu hiện tại → lỗi Zod dưới New Password.
- [ ] Sai mật khẩu hiện tại → lỗi server dưới Current Password; **vẫn ở tab Change Password**.
- [ ] Đổi thành công → 3 ô về rỗng, vẫn ở tab này, alert "Password updated.", vẫn đăng nhập.
- [ ] Mở một trình duyệt khác đã đăng nhập cùng tài khoản → sau khi đổi mật khẩu, reload ở trình duyệt đó bị đá về `/login`.
- [ ] Nút mắt hiện/ẩn mật khẩu hoạt động ở cả 3 ô.
- [ ] Sai mật khẩu hiện tại 5 lần → thông báo "Too many password change attempts…".

### Chung

- [ ] Ở độ rộng mobile: form Account xếp một cột; trang Login/Register ẩn ảnh bên trái.
- [ ] Tab bàn phím đi qua được mọi field, nút tab, nút mắt, nút submit.

---

## 8. Đã cân nhắc và cố ý bỏ

| Bỏ qua                                  | Lý do                                                                                                   |
| --------------------------------------- | ------------------------------------------------------------------------------------------------------- |
| Tab Account/Password trên URL (`?tab=`) | Backend chỉ có một `GET /account`; tab là trạng thái UI thuần (D4).                                     |
| `FormField` cho Login / Register        | Giữ `FormField` trong phạm vi trang Account (D6).                                                       |
| Kiểm tra trùng email phía client        | Cần gọi server; backend đã trả lỗi `unique` trên field `email`.                                         |
| Regex mật khẩu trên form Login (client) | Không báo "mật khẩu yếu" trước khi server kiểm tra thông tin đăng nhập; backend vẫn áp `ValidPassword`. |
| Zustand cho trạng thái đăng nhập        | `frontend.md`: auth state là server state → đọc từ `usePage().props.auth`.                              |

---

## 9. Follow-up (ngoài phạm vi task này)

- **Chuyển alert flash sang toast `sonner`** ở `Pages/Account/Index.tsx` và `Pages/Login/Index.tsx` qua `FlashToasts` — quy ước hiện tại của dự án cho kết quả mutation (đã ghi ở [`tasks/categories/frontend.md`](../categories/frontend.md) §10).
- **Link "Forgot Password?" trỏ tới `/forgot-password` chưa tồn tại** → 404. Quên mật khẩu nằm ngoài Phase 1, nên ẩn link này cho tới khi làm.
- **`SocialButtons` chỉ là link ra google.com / facebook.com / apple.com**, không có đăng nhập mạng xã hội → nên bỏ hoặc ẩn.
- **Chữ còn sót từ template Trezo**: tiêu đề "Welcome back to Trezo!", placeholder `example@trezo.com`, fallback `'Olivia John'` / `'Marketing Manager'` trong dropdown Header (`User.role` không được backend gửi).
- `Flash` type còn các key `success`, `info`, `warning` mà backend không bao giờ gửi; `User.avatar`, `User.role` cũng vậy.
- `ProfileForm` còn icon trong input (`ps-5` + `<i>`), khác với quy ước form mới (Categories) là **không** icon trong form control. Chỉ đổi nếu muốn đồng bộ giao diện.
- Login / Register chưa dùng `PasswordInput` (không có nút hiện/ẩn mật khẩu) như trang Account.
- i18n toàn dự án (kế thừa [`tasks/accounts/backend.md`](backend.md) §9).
- Hạ tầng test frontend (kế thừa Categories frontend Q18).
