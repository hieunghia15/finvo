# Task — Accounts (Backend)

> Phase: **1**.
> Nguồn: [`features/phase-1.md`](../../features/phase-1.md) §5 + [`phases/phase-1.md`](../../phases/phase-1.md) §4.6, §4.7, §8.
> Phạm vi: **chỉ backend** — đăng ký (kèm onboarding), đăng nhập, đăng xuất, xem/cập nhật tài khoản, đổi mật khẩu. Page React là task riêng: [`frontend.md`](frontend.md).
> Tài liệu này được viết **sau khi triển khai** (as-built), rút từ code hiện tại, để các module sau (Wallets, Transactions…) có một bản tham chiếu cùng khuôn với Categories.

---

## 0. Tình trạng hiện tại

Toàn bộ chức năng **đã xong và đã merge**.

| Thành phần                                                   | PR / commit                         | Trạng thái |
| ------------------------------------------------------------ | ----------------------------------- | ---------- |
| Đăng nhập / đăng xuất, `AuthService`, `LoginRequest`         | #3, #4 (`feat/authentication`)      | ✅         |
| Chuẩn hóa email, rate limit login/register                   | #9 (`fix/authentication`)           | ✅         |
| Rule `ValidEmail` / `ValidPassword`                          | #10 (`fix/rule-password`)           | ✅         |
| `UserOnboardingService` (ví + danh mục mặc định khi đăng ký) | #12 (`feat/create-erd-migration`)   | ✅         |
| `AccountController`, `AccountService`, đổi tên, đổi mật khẩu | #14 (`feat/implement-user-backend`) | ✅         |
| Feature test (`AuthTest`, `tests/Feature/Account/*`)         | #4 → #14                            | ✅         |

Bảng `users` **giữ nguyên** migration mặc định của Laravel (`0001_01_01_000000_create_users_table.php`). Cột `password` lưu hash qua cast `hashed`; "password_hash" ở `features/phase-1.md` §5.4 được hiểu là "lưu dạng hash", không đổi tên cột (plan §3).

---

## 1. Bảng quyết định

Rút từ plan §4.6, §4.7 và từ code đã merge.

| #   | Quyết định                                                                                                                                                                 |
| --- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| D1  | Xác thực bằng session guard `web` (cookie), không Sanctum/API. Controller trả Inertia response hoặc redirect.                                                              |
| D2  | Đăng ký **không tự đăng nhập**: tạo user → redirect `/login` với `flash.status = "Account created. Please log in."`.                                                       |
| D3  | Tạo user + ví mặc định + 11 danh mục mặc định trong **một DB transaction** (`AuthService::register` → `UserOnboardingService::createDefaults`). Lỗi giữa chừng → rollback. |
| D4  | Email được **lowercase** trong `prepareForValidation` của cả Login lẫn Register → unique và đăng nhập không phân biệt hoa/thường, không phụ thuộc collation.               |
| D5  | Rule mật khẩu dùng chung (`App\Rules\ValidPassword`): 6–32 ký tự, có chữ hoa, chữ thường, số, ký tự đặc biệt `#?!@$%^&*-`. Frontend giữ bản regex y hệt.                   |
| D6  | Trang tài khoản chỉ gửi **3 field whitelist**: `name`, `email`, `created_at`. Không bao giờ gửi `password`, `remember_token`, `id`.                                        |
| D7  | Cập nhật tài khoản: **chỉ `name`**. `email` trong payload bị FormRequest loại bỏ (không lỗi, không đổi).                                                                   |
| D8  | Đổi mật khẩu: `current_password:web` + `confirmed` + `different:current_password` + `ValidPassword`. Sau khi đổi gọi `Auth::logoutOtherDevices()`.                         |
| D9  | Middleware `auth.session` trên toàn bộ group đã đăng nhập → session ở thiết bị khác bị đăng xuất khi hash mật khẩu thay đổi.                                               |
| D10 | Rate limit trả về **lỗi gắn field** (redirect back + `withErrors`), không phải trang 429 trần, để Inertia hiển thị như mọi lỗi validation khác.                            |
| D11 | Một controller cho mỗi domain: `AccountController` gom cả profile lẫn password (không tách `PasswordController`).                                                          |
| D12 | Controller chỉ gọi Service; logic nằm ở `AuthService`, `AccountService`, `UserOnboardingService`. Không Repository.                                                        |
| D13 | Backend giữ chuỗi **tiếng Anh** (`"Account updated."`, `"Password updated."`…). i18n là nợ kỹ thuật chung (§9). _→ Thay bởi [multi-lang](../multi-lang/backend.md)._       |

---

## 2. Danh sách file

```
app/Http/Controllers/Auth/AuthenticatedSessionController.php
app/Http/Controllers/Auth/RegisteredUserController.php
app/Http/Controllers/AccountController.php
app/Http/Requests/Auth/LoginRequest.php
app/Http/Requests/Auth/RegisterRequest.php
app/Http/Requests/Account/UpdateAccountRequest.php
app/Http/Requests/Account/UpdatePasswordRequest.php
app/Rules/ValidEmail.php
app/Rules/ValidPassword.php
app/Services/AuthService.php
app/Services/AccountService.php
app/Services/UserOnboardingService.php
app/Providers/AppServiceProvider.php            (rate limiter login / register / password-update)
app/Http/Middleware/HandleInertiaRequests.php   (shared props auth.user, flash)
app/Models/User.php                             (casts, quan hệ wallets/categories/transactions)
database/seeders/AdminUserSeeder.php
routes/web.php
tests/Feature/AuthTest.php
tests/Feature/Account/ShowAccountTest.php
tests/Feature/Account/UpdateAccountTest.php
tests/Feature/Account/UpdatePasswordTest.php
```

---

## 3. Routes

```php
Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:register');
});

// "auth.session" logs out other sessions once the user's password changes.
Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::patch('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])
        ->middleware('throttle:password-update')
        ->name('account.password.update');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
```

- User đã đăng nhập vào `/`, `/login`, `/register` → redirect `/dashboard` (middleware `guest`).
- Guest vào bất kỳ route nào trong group `auth` → redirect `/login`.

### Rate limit

| Limiter           | Giới hạn         | Khóa theo             | Field nhận lỗi     |
| ----------------- | ---------------- | --------------------- | ------------------ |
| `login` (route)   | 10 / phút        | IP                    | `email`            |
| `AuthService`     | 5 lần sai / 60 s | `lower(email)` + IP   | `email`            |
| `register`        | 5 / phút         | IP                    | `email`            |
| `password-update` | 5 / phút         | user id (fallback IP) | `current_password` |

Hai lớp cho login: lớp route chặn một IP dò nhiều email; lớp `AuthService` chặn dò một email mà không khóa người dùng thật ở IP khác. Response của limiter **không** giữ lại `current_password`, `password`, `password_confirmation` trong old input.

---

## 4. Contract của props (Inertia)

### Shared props (mọi page) — `HandleInertiaRequests::share()`

```ts
{
    auth: {
        user: { id: number; name: string; email: string; email_verified_at: string | null; created_at: string } | null;
    };
    flash: {
        status: string | null;   // thông báo thành công
        error: string | null;    // lỗi không gắn được field (dùng từ Categories)
    };
}
```

`password`, `remember_token` **không** bao giờ nằm trong `auth.user` (có test).

### `Account/Index` — `AccountController@show`

```ts
{
    account: {
        name: string;
        email: string;
        created_at: string; // ISO 8601, UTC
    }
}
```

### `Login/Index`, `Register/Index`

Không có prop riêng; Login đọc `flash.status` sau khi đăng ký.

---

## 5. Đặc tả từng file

### 5.1. `app/Rules/ValidEmail.php`, `app/Rules/ValidPassword.php`

| Rule            | Regex                                                                | Thông báo                                                                                                                                        |
| --------------- | -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| `ValidEmail`    | `/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,64})+$/`   | `The :attribute field must be a valid email address.`                                                                                            |
| `ValidPassword` | `/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,32}$/` | `The :attribute must be 6–32 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.` |

Hai regex này được sao y ở `resources/js/Schemas/auth.schema.ts` (`emailRegex`, `passwordRegex`). Sửa một bên thì **phải** sửa bên còn lại.

### 5.2. `app/Http/Requests/Auth/LoginRequest.php`

- `prepareForValidation()`: `Str::lower(email)`.
- Rules: `email` → `required`, `ValidEmail`; `password` → `required`, `string`, `ValidPassword`; `remember` → `sometimes`, `boolean`.
- `credentials()` trả `['email', 'password']` cho `AuthService::login`.

### 5.3. `app/Http/Requests/Auth/RegisterRequest.php`

- `prepareForValidation()`: `Str::lower(email)`. `TrimStrings` đã cắt khoảng trắng đầu/cuối.
- Rules: `name` → `required`, `string`, `max:255`; `email` → `required`, `string`, `max:255`, `unique:users,email`, `ValidEmail`; `password` → `required`, `string`, `confirmed`, `ValidPassword`.
- `userData()` trả `['name', 'email', 'password']`.

### 5.4. `app/Http/Requests/Account/UpdateAccountRequest.php`

- `prepareForValidation()`: `Str::squish(name)` — gộp khoảng trắng liên tiếp (plan §1 "Chuẩn hóa chuỗi"). Khuôn này được Categories dùng lại.
- Rules: chỉ `name` → `required`, `string`, `max:255`. Mọi field khác (kể cả `email`) bị loại khỏi `validated()` (D7).

### 5.5. `app/Http/Requests/Account/UpdatePasswordRequest.php`

| Field              | Rules                                                                            |
| ------------------ | -------------------------------------------------------------------------------- |
| `current_password` | `required`, `string`, `current_password:web`                                     |
| `password`         | `required`, `string`, `confirmed`, `different:current_password`, `ValidPassword` |

Lỗi `confirmed` và `different` đều báo trên field `password`.

### 5.6. `app/Services/AuthService.php`

```php
public function login(Request $request, array $credentials, bool $remember = false): void;
public function register(array $data): User;
public function logout(Request $request): void;
protected function throttleKey(Request $request, string $email): string; // Str::transliterate(lower(email).'|'.ip)
```

- `login()`: kiểm tra `RateLimiter::tooManyAttempts` (5 lần) → `Auth::attempt` → sai thì `hit` 60 s và ném `ValidationException` trên `email` (`auth.failed`) → đúng thì `clear` + `session()->regenerate()` (chống session fixation).
- `register()`: `DB::transaction` { `User::create` → `onboarding->createDefaults($user)` } → `event(new Registered($user))`.
    - Bắt `UniqueConstraintViolationException` (race: hai request cùng email vượt qua rule `unique`) và đổi thành `ValidationException` trên `email`, giống thông báo của rule `unique`.
- `logout()`: `Auth::guard('web')->logout()` → `session()->invalidate()` → `regenerateToken()`.

### 5.7. `app/Services/UserOnboardingService.php`

- `DEFAULT_WALLET_NAME = 'Ngân hàng'` — `type = bank`, `currency_code = VND`, `initial_balance = 0`, `status = active` (default model).
- `DEFAULT_CATEGORIES`:
    - **income:** Lương, Thưởng, Thu nhập khác
    - **expense:** Ăn uống, Di chuyển, Mua sắm, Nhà ở, Hóa đơn, Giải trí, Sức khỏe, Chi phí khác
- `createDefaults(User $user)` dùng `firstOrCreate` → **idempotent**; không tự mở transaction, caller phải bọc (D3). `DemoDataSeeder` cũng gọi lại hàm này cho tài khoản admin.
- Phụ thuộc: currency `VND` phải có sẵn → `CurrencySeeder` bắt buộc chạy ở mọi môi trường (plan §3.1).

### 5.8. `app/Services/AccountService.php`

```php
public function updateName(User $user, string $name): void;
public function changePassword(User $user, string $newPassword): void;
```

- `changePassword()`: `update(['password' => $newPassword])` (cast `hashed` tự hash) → `Auth::logoutOtherDevices($newPassword)`.
    - Thứ tự bắt buộc: `logoutOtherDevices` kiểm tra mật khẩu truyền vào với hash **đang lưu**, nên phải chạy **sau** khi update. Nó cũng phát lại cookie "remember me" của thiết bị hiện tại (cookie nhúng hash mật khẩu).
    - `$user` phải là user đang đăng nhập trên guard hiện tại, vì `logoutOtherDevices` tác động lên user đó.

### 5.9. Controllers

| Controller                       | Method           | Request                 | Trả về                                                                            |
| -------------------------------- | ---------------- | ----------------------- | --------------------------------------------------------------------------------- |
| `AuthenticatedSessionController` | `create`         | –                       | `Inertia::render('Login/Index')`                                                  |
|                                  | `store`          | `LoginRequest`          | `redirect()->intended('/dashboard')`                                              |
|                                  | `destroy`        | `Request`               | `redirect()->route('login')`                                                      |
| `RegisteredUserController`       | `create`         | –                       | `Inertia::render('Register/Index')`                                               |
|                                  | `store`          | `RegisterRequest`       | `redirect()->route('login')->with('status', 'Account created. Please log in.')`   |
| `AccountController`              | `show`           | `Request`               | `Inertia::render('Account/Index', ['account' => …only(name, email, created_at)])` |
|                                  | `update`         | `UpdateAccountRequest`  | `redirect()->route('account.show')->with('status', 'Account updated.')`           |
|                                  | `updatePassword` | `UpdatePasswordRequest` | `redirect()->route('account.show')->with('status', 'Password updated.')`          |

Mutation của Account redirect về **route cố định** `account.show` (không phải `back()` như Categories) vì trang không có query string cần giữ.

### 5.10. `app/Providers/AppServiceProvider.php`

`configureRateLimiting()` định nghĩa 3 limiter ở §3. `throttledResponse($message, $field = 'email')` dựng response: `back()->withInput(except mật khẩu)->withErrors([$field => __($message, ['seconds', 'minutes'])])`.

### 5.11. `database/seeders/AdminUserSeeder.php`

`updateOrCreate` tài khoản `finvo@gmail.com` / tên `Finvo Admin`, mật khẩu cố định trong source.

Thứ tự trong `DatabaseSeeder`:

| Seeder            | Môi trường                        | Lý do                                                                                                   |
| ----------------- | --------------------------------- | ------------------------------------------------------------------------------------------------------- |
| `CurrencySeeder`  | **mọi** môi trường                | Đăng ký cần currency `VND` (plan §3.1).                                                                 |
| `AdminUserSeeder` | mọi môi trường **trừ production** | Mật khẩu nằm trong source. Ở production, tài khoản chỉ được tạo qua `/register` (có onboarding đầy đủ). |
| `DemoDataSeeder`  | chỉ `local`                       | Ví, danh mục, giao dịch mẫu cho tài khoản admin.                                                        |

---

## 6. Checklist test

Tất cả đã có và đang pass. Các mục đánh dấu _(§8)_ là edge case bắt buộc trong [`phases/phase-1.md`](../../phases/phase-1.md) §8.

### `AuthTest` — Login

- [x] Guest xem được `/login` (component `Login/Index`).
- [x] Đăng nhập đúng → redirect `/dashboard`, đã xác thực.
- [x] Sai mật khẩu / email không tồn tại / email sai định dạng → lỗi trên `email`, vẫn là guest.
- [x] Thiếu `email` / thiếu `password` → lỗi đúng field.
- [x] 5 lần sai cho cùng email + IP → lần 6 bị chặn dù đúng mật khẩu.
- [x] 10 request / phút từ một IP (email khác nhau) → lỗi `email` bắt đầu bằng `"Too many login attempts."`.
- [x] Email khác hoa/thường vẫn đăng nhập được; `LoginRequest::credentials()` trả email lowercase.
- [x] Đăng nhập tạo session id mới.

### `AuthTest` — Register

- [x] Guest xem được `/register`.
- [x] Đăng ký hợp lệ → redirect `/login`, `flash.status`, **không** tự đăng nhập, `name` được trim, `email` lowercase, mật khẩu đã hash.
- [x] Tạo đúng 1 ví "Ngân hàng" (bank, VND, `0.0000`, active) + đủ danh mục mặc định theo từng type. _(§8)_
- [x] Onboarding lỗi → rollback, không còn user nào. _(§8)_
- [x] Phát event `Registered`.
- [x] Trang login hiển thị `flash.status` sau khi đăng ký.
- [x] Thiếu field / email trùng / email trùng khác hoa-thường / mật khẩu yếu / xác nhận không khớp / `name` hoặc `email` quá 255 → lỗi đúng field, không tạo user.
- [x] Race email trùng giữa validation và insert → `ValidationException` trên `email`.
- [x] User đã đăng nhập gọi `POST /register` → redirect `/dashboard`, không tạo user.
- [x] 5 request / phút → lỗi `email` "Too many registration attempts…", không giữ `password` trong old input.

### `AuthTest` — Session & shared props

- [x] `auth.user` có `id`, `email`, **không** có `password`, `remember_token`.
- [x] Đăng xuất → redirect `/login`, thành guest; guest gọi `/logout` → redirect `/login`.
- [x] Guest vào `/dashboard` → `/login`; user đã đăng nhập vào `/`, `/login`, `/register` → `/dashboard`.
- [x] Mật khẩu bị đổi ở nơi khác → session hiện tại bị đăng xuất ở request tiếp theo (`auth.session`).

### `ShowAccountTest`

- [x] Guest → redirect `/login`.
- [x] Chỉ trả `name`, `email`, `created_at` — assert scoped, **fail nếu lộ thêm field**.

### `UpdateAccountTest`

- [x] Đổi tên thành công → redirect `/account`, `flash.status = "Account updated."`.
- [x] Tên có khoảng trắng thừa / tab → được trim và gộp.
- [x] Thiếu / rỗng / chỉ khoảng trắng / 256 ký tự / không phải chuỗi → lỗi `name`, tên cũ giữ nguyên.
- [x] Gửi kèm `email` → email **không** đổi. _(§8)_
- [x] Guest → redirect `/login`.

### `UpdatePasswordTest`

- [x] Đổi thành công → redirect `/account`, `flash.status = "Password updated."`, **vẫn** đăng nhập.
- [x] Sau khi đổi: mật khẩu cũ không đăng nhập được, mật khẩu mới đăng nhập được.
- [x] Phát event `OtherDeviceLogout` → các session khác bị đăng xuất. _(§8)_
- [x] Sai mật khẩu hiện tại → lỗi `current_password`, không giữ mật khẩu trong old input.
- [x] Thiếu field / xác nhận không khớp / mật khẩu yếu / trùng mật khẩu hiện tại → lỗi đúng field.
- [x] 5 lần / phút cho **một user** → lỗi `current_password` "Too many password change attempts…"; user khác không bị ảnh hưởng.
- [x] Guest → redirect `/login`.

---

## 7. Kiểm chứng

```
./vendor/bin/pint
./vendor/bin/phpstan analyse
php artisan test --filter='AuthTest|Account'
```

Trước khi sửa bất kỳ symbol nào ở trên (ví dụ `AuthService::register`, `HandleInertiaRequests::share`), chạy impact analysis theo `CLAUDE.md`:

```
node .gitnexus/run.cjs impact "register" --direction upstream --repo .
node .gitnexus/run.cjs detect-changes --scope all --repo .
```

---

## 8. Ngoài phạm vi Phase 1 (cố ý bỏ)

Theo [`phases/phase-1.md`](../../phases/phase-1.md) §0 — không implement, không tạo cột/bảng.

| Bỏ qua                         | Ghi chú                                                                                        |
| ------------------------------ | ---------------------------------------------------------------------------------------------- |
| Đổi email                      | `UpdateAccountRequest` chỉ nhận `name`.                                                        |
| Xác thực email bắt buộc        | Cột `email_verified_at` giữ nguyên, không dùng; `User` không implement `MustVerifyEmail`.      |
| Quên mật khẩu / reset mật khẩu | Bảng `password_reset_tokens` có sẵn từ migration mặc định nhưng không có route nào dùng.       |
| Xóa tài khoản                  | FK `ON DELETE CASCADE` từ `wallets`, `categories`, `transactions` đã sẵn sàng nếu sau này làm. |
| Đăng nhập mạng xã hội          | Không có OAuth; nút Google/Facebook/Apple ở frontend chỉ là markup template.                   |
| Tự đăng nhập sau khi đăng ký   | D2 — user được đưa về `/login`.                                                                |

---

## 9. Nợ kỹ thuật / follow-up

- ~~`AdminUserSeeder` chạy ở mọi môi trường với mật khẩu cố định~~ — **đã sửa**: `DatabaseSeeder` bỏ qua seeder này khi `app()->isProduction()`; production chỉ có tài khoản do người dùng tự đăng ký (§5.11).
- ✅ Đã có task [`tasks/multi-lang/backend.md`](../multi-lang/backend.md). ~~**Chưa có i18n**~~ (kế thừa [`tasks/categories/backend.md`](../categories/backend.md) §9): thông báo flash, rule message và `auth.failed` / `auth.throttle` đều là tiếng Anh. Cần task riêng: `lang/vi/`, `APP_LOCALE=vi`.
- **Thông báo lỗi của `ValidEmail` / `ValidPassword` là chuỗi cứng** trong class, không qua `__()` → sẽ phải sửa khi làm i18n.
- `auth.user` đang gửi `email_verified_at` dù Phase 1 không dùng xác thực email; có thể bỏ khi dọn shared props.
- Checklist [`phases/phase-1.md`](../../phases/phase-1.md) §8, mục "Đăng ký: tạo đúng 1 ví … lỗi giữa chừng thì rollback", vẫn để `[ ]` dù test đã có (`test_registration_creates_default_wallet_and_categories`, `test_registration_is_rolled_back_when_default_data_fails`) — có thể tick.
