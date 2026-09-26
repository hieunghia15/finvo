# Task — Multi-language (Backend)

> Phase: **1**.
> Nguồn: [`features/phase-1.md`](../../features/phase-1.md) §6 + [`phases/phase-1.md`](../../phases/phase-1.md) §9 + template `multi-lang.html` + buổi grill Q1–Q26.
> Phạm vi: **backend** — cookie ngôn ngữ, middleware, shared props, file `lang/`, dịch mọi chuỗi PHP, seed theo ngôn ngữ, script `lang:check`, test. React là [`frontend.md`](frontend.md).
> Nhánh: `feat/implement-multi-lang` — **một PR chung** với frontend (Q22).
>
> **Trạng thái: ✅ đã triển khai** (chưa commit). Pint, PHPStan, `php artisan test` (142/142) và `npm run lang:check` đều pass. Còn một bước sửa tay: `.env.example` và `.env` của từng máy phải có `APP_LOCALE=vi` — file `.env*` nằm ngoài quyền chỉnh sửa của agent, và `APP_LOCALE=en` mặc định của Laravel sẽ ghi đè default `vi` trong `config/app.php`.

---

## 0. Tình trạng hiện tại

| Thành phần                                                                      | Trạng thái                                                                          |
| ------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| Thư mục `lang/`                                                                 | ❌ chưa có                                                                          |
| `config/app.php`: `locale`, `fallback_locale`                                   | `env('APP_LOCALE', 'en')`, `env('APP_FALLBACK_LOCALE', 'en')`                       |
| `resources/views/app.blade.php`: `<html lang="{{ app()->getLocale() }}">`       | ✅ có — tự đúng khi locale được set                                                 |
| `HandleInertiaRequests::share()`                                                | có `auth.user`, `flash.status`, `flash.error`; **chưa** có `locale`, `translations` |
| `inertia-laravel` hỗ trợ once prop (`Inertia::once()`, `->as()`, `shareOnce()`) | ✅ có trong vendor                                                                  |
| Enum `label()` qua `HasOptions::translate()` → `__()`                           | có, nhưng **không được gọi ở đâu** (frontend tự sở hữu nhãn)                        |
| Test đang assert chuỗi tiếng Anh (`'Account updated.'`, `'Too many …'`)         | nhiều — xử lý bằng `APP_LOCALE=en` trong `phpunit.xml` (Q13)                        |
| CI `.github/workflows/linter.yml`                                               | Prettier + Pint + PHPStan; **không** chạy test                                      |

Chuỗi PHP đang cứng tiếng Anh (phải dịch):

| File                                                 | Chuỗi                                                                                           |
| ---------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| `AccountController`                                  | `Account updated.`, `Password updated.`                                                         |
| `Auth/RegisteredUserController`                      | `Account created. Please log in.`                                                               |
| `CategoryController`                                 | `Category created.`, `Category updated.`, `Category status updated.`, `Category deleted.`       |
| `Exceptions/CategoryInUseException`                  | `This category still has transactions and cannot be deleted.`                                   |
| `Requests/Category/StoreCategoryRequest::messages()` | `You already have a category with this name and type.`                                          |
| `Requests/Category/UpdateCategoryRequest`            | `messages()` như trên + 2 lỗi trong `after()` (archived, type bị khóa)                          |
| `Rules/ValidEmail`, `Rules/ValidPassword`            | message trong `$fail(...)`                                                                      |
| `Providers/AppServiceProvider`                       | 2 thông báo throttle (register, password-update); `auth.throttle` (login)                       |
| `Services/AuthService`                               | `auth.throttle`, `auth.failed`, `validation.unique` — đã là key, chỉ thiếu file `lang/vi/*.php` |
| `Services/UserOnboardingService`                     | tên ví + 11 danh mục mặc định (đang tiếng Việt cứng)                                            |

---

## 1. Bảng quyết định (grill Q1–Q26, phần backend)

| #   | Quyết định                                                                                                                                           |
| --- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Q1  | `vi` + `en`. Mặc định `vi`, fallback `en`.                                                                                                           |
| Q2  | Chỉ **cookie** `locale`. Không cột `users.locale`, không đồng bộ thiết bị. Cookie sống qua logout (`session()->invalidate()` không đụng tới cookie). |
| Q3  | Một nguồn bản dịch ở `lang/`; frontend nhận qua Inertia, không có file dịch phía JS.                                                                 |
| Q7  | Key là **câu tiếng Anh**. Chỉ `lang/vi.json`. Key dạng group (`auth.failed`, `validation.unique`) vẫn dùng file PHP.                                 |
| Q8  | `locale` là shared prop thường; `translations` là once prop với key `translations.{locale}`.                                                         |
| Q9  | `PUT /locale` + `UpdateLocaleRequest` → cookie 1 năm → `back()`. Không throttle.                                                                     |
| Q10 | Không cookie hoặc cookie không hợp lệ → `config('app.locale')`. Không đọc `Accept-Language`.                                                         |
| Q11 | Seed theo `app()->getLocale()` **lúc đăng ký**. `DemoDataSeeder` luôn `vi`.                                                                          |
| Q13 | `phpunit.xml`: `APP_LOCALE=en`. Test đa ngôn ngữ đặt cookie `vi` một cách tường minh.                                                                |
| Q16 | `php artisan lang:publish`; tự dịch `lang/vi/validation.php` **đầy đủ** + `attributes`, và `lang/vi/auth.php`. Không package `laravel-lang`.         |
| Q19 | `npm run lang:check` quét cả PHP lẫn TS; chạy trong CI.                                                                                              |
| Q24 | Hằng số onboarding là key tiếng Anh (bảng ở plan §9.3), dịch bằng `__()` khi tạo.                                                                    |
| Q25 | Thuật ngữ và giọng văn theo plan §9.2.                                                                                                               |

Quyết định cũ bị thay thế: `tasks/categories/backend.md` Q15 ("backend giữ chuỗi tiếng Anh") và §9 ("chưa có i18n"); `tasks/accounts/backend.md` D13.

---

## 2. Danh sách file

**Tạo mới**

```
app/Enums/Locale.php
app/Http/Middleware/SetLocale.php
app/Http/Requests/Locale/UpdateLocaleRequest.php
app/Http/Controllers/LocaleController.php
app/Services/LocaleService.php
lang/vi.json
lang/en/{auth,pagination,passwords,validation}.php   (php artisan lang:publish, không sửa)
lang/vi/{auth,pagination,passwords,validation}.php
scripts/lang-check.mjs
tests/Feature/LocaleTest.php
tests/Unit/LangFilesTest.php
```

**Sửa**

```
config/app.php                                    (default locale 'vi')
.env.example                                      (APP_LOCALE=vi, APP_FALLBACK_LOCALE=en) ⚠️ sửa tay — xem §0
phpunit.xml                                       (+ APP_LOCALE=en)
bootstrap/app.php                                 (+ SetLocale trước HandleInertiaRequests)
routes/web.php                                    (+ PUT /locale)
app/Http/Middleware/HandleInertiaRequests.php     (+ locale, + shareOnce translations)
app/Http/Controllers/AccountController.php        (__() flash)
app/Http/Controllers/Auth/RegisteredUserController.php
app/Http/Controllers/CategoryController.php
app/Exceptions/CategoryInUseException.php
app/Http/Requests/Category/StoreCategoryRequest.php
app/Http/Requests/Category/UpdateCategoryRequest.php
app/Rules/ValidEmail.php
app/Rules/ValidPassword.php
app/Providers/AppServiceProvider.php              (throttle messages)
app/Services/UserOnboardingService.php            (key tiếng Anh + __())
database/seeders/DemoDataSeeder.php               (ép locale vi, tra ví theo __())
package.json                                      (+ script lang:check)
.github/workflows/linter.yml                      (+ bước lang:check)
```

**Không đụng tới:** migration (không thêm cột), `HasOptions` / `label()` của enum (không có caller — để nguyên), `AuthService` (đã dùng key), `app.blade.php`.

---

## 3. Routes

Thêm **ngoài** hai group `guest` và `auth` (cả hai đều cần gọi được):

```php
Route::put('/locale', [LocaleController::class, 'update'])->name('locale.update');
```

---

## 4. Contract với frontend

Shared props bổ sung trên **mọi** Inertia response (kể cả `Login/Index`, `Register/Index`):

```ts
{
    locale: 'vi' | 'en'; // mỗi request
    translations: Record<string, string>; // once prop, key "translations.{locale}"
}
```

- `locale = 'vi'` → `translations` = nội dung `lang/vi.json` (object phẳng: câu tiếng Anh → câu tiếng Việt).
- `locale = 'en'` → `translations = {}` (key chính là câu hiển thị). Không đọc file nào.
- Once prop: client gửi header các once prop đã có; server bỏ qua `translations.vi` nếu client đã giữ. Đổi sang `en` → key `translations.en` chưa có → server gửi. Đổi lại `vi` → client đã có `translations.vi` từ trước nên không gửi lại.
- Chuỗi trong `flash.*`, `errors.*` được backend dịch sẵn theo locale của request → frontend **không** dịch lại.

`PUT /locale`:

| Payload            | Kết quả                                                                          |
| ------------------ | -------------------------------------------------------------------------------- |
| `{ locale: 'en' }` | redirect `back()` + `Set-Cookie: locale=<encrypted en>; Max-Age=1 năm; HttpOnly` |
| `{ locale: 'fr' }` | lỗi validation trên field `locale` (redirect back), cookie không đổi             |

---

## 5. Đặc tả từng file

### 5.1. `app/Enums/Locale.php`

```php
enum Locale: string
{
    case Vi = 'vi';
    case En = 'en';
}
```

Chỉ hai case, **không** `use HasOptions` (không có nơi nào cần `options()`). Dùng cho `Rule::enum()` và `Locale::tryFrom()` trong middleware. Bảng locale → Intl (`vi-VN`, `en-GB`) do frontend giữ (frontend §4.1).

### 5.2. `app/Services/LocaleService.php`

```php
public const COOKIE = 'locale';

/** One year, in minutes. */
public const COOKIE_MINUTES = 525_600;

/**
 * The locale stored in the request's cookie, or null when absent or unsupported.
 */
public function fromRequest(Request $request): ?Locale;

/**
 * @param  array{locale: string}  $data  Validated UpdateLocaleRequest data.
 */
public function rememberCookie(array $data): Cookie;   // cookie(self::COOKIE, $data['locale'], self::COOKIE_MINUTES)

/**
 * Flat dictionary sent to the frontend: lang/vi.json for vi, [] for en.
 *
 * @return array<string, string>
 */
public function dictionary(string $locale): array;
```

- `dictionary()` đọc `lang_path("{$locale}.json")` nếu file tồn tại, `json_decode(..., flags: JSON_THROW_ON_ERROR)`; `en` không có file → `[]`.
- Không cache: file vài chục KB, và once prop đã chặn việc gửi lại. Nếu sau này cần, dùng `Cache::rememberForever` theo `filemtime`.
- Cookie để HttpOnly (mặc định): frontend đọc `locale` từ props, không đọc cookie.

### 5.3. `app/Http/Middleware/SetLocale.php`

```php
public function handle(Request $request, Closure $next): Response
{
    if ($locale = $this->localeService->fromRequest($request)) {
        App::setLocale($locale->value);
    }

    return $next($request);
}
```

- Không có cookie hợp lệ → giữ `config('app.locale')` (Q10). Nhờ vậy test chạy `en` nhờ `phpunit.xml`, còn dev/prod chạy `vi`.
- Đăng ký trong `bootstrap/app.php`, **trước** `HandleInertiaRequests` (shared props cần locale đã set):

    ```php
    $middleware->web(append: [
        SetLocale::class,
        HandleInertiaRequests::class,
    ]);
    ```

- Chạy sau `EncryptCookies` (nằm sẵn đầu group `web`) → `$request->cookie('locale')` đã được giải mã.
- Chạy trước route middleware (`throttle:*`, `auth`) và trước FormRequest → thông báo throttle và validation ra đúng ngôn ngữ.

### 5.4. `app/Http/Requests/Locale/UpdateLocaleRequest.php`

| Field    | Rules                                   |
| -------- | --------------------------------------- |
| `locale` | `required`, `Rule::enum(Locale::class)` |

### 5.5. `app/Http/Controllers/LocaleController.php`

```php
public function update(UpdateLocaleRequest $request): RedirectResponse
{
    $cookie = $this->localeService->rememberCookie($request->validated());

    return back()->withCookie($cookie);
}
```

Không flash message: bản thân giao diện đổi ngôn ngữ đã là phản hồi.

### 5.6. `app/Http/Middleware/HandleInertiaRequests.php`

```php
// share(): thêm
'locale' => fn () => app()->getLocale(),

/**
 * The dictionary is sent once and kept by the client across navigations. Its
 * key carries the locale, so switching language makes the server send the new one.
 */
public function shareOnce(Request $request): array
{
    $locale = app()->getLocale();

    return [
        'translations' => Inertia::once(fn () => $this->localeService->dictionary($locale))
            ->as("translations.{$locale}"),
    ];
}
```

Inject `LocaleService` qua constructor. Trước khi sửa: `node .gitnexus/run.cjs impact "share" --direction upstream --repo .`.

### 5.7. `config/app.php`, `.env.example`, `phpunit.xml`

- `config/app.php`: `'locale' => env('APP_LOCALE', 'vi')`, `'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en')`, `'faker_locale'` giữ nguyên.
- `.env.example`: `APP_LOCALE=vi`, `APP_FALLBACK_LOCALE=en`. Nhắc trong PR: mỗi dev tự sửa `.env` của mình.
- `phpunit.xml`: `<env name="APP_LOCALE" value="en"/>`.

### 5.8. File `lang/`

1. `php artisan lang:publish` → `lang/en/{auth,pagination,passwords,validation}.php`. **Không sửa** các file en.
2. `lang/vi/validation.php`: dịch **mọi** key của bản en (kể cả mảng con `between`, `gt`, `size`…), cùng thứ tự key. Mảng `attributes`:

    | Field                   | vi                  |
    | ----------------------- | ------------------- |
    | `name`                  | tên                 |
    | `email`                 | email               |
    | `password`              | mật khẩu            |
    | `password_confirmation` | xác nhận mật khẩu   |
    | `current_password`      | mật khẩu hiện tại   |
    | `type`                  | loại                |
    | `status`                | trạng thái          |
    | `include_archived`      | hiển thị đã lưu trữ |
    | `locale`                | ngôn ngữ            |

    Ví dụ: `'required' => 'Trường :attribute là bắt buộc.'` → "Trường tên là bắt buộc.".

3. `lang/vi/auth.php`: `failed` ("Email hoặc mật khẩu không đúng."), `password`, `throttle` (giữ `:seconds`).
4. `lang/vi/pagination.php`, `lang/vi/passwords.php`: dịch cho đủ bộ (Transactions sẽ phân trang; passwords chưa dùng).
5. `lang/vi.json`: object phẳng, **sắp xếp key theo alphabet**, gồm:
    - mọi chuỗi PHP ở §0 (flash, exception, messages, rule, throttle, tên seed §5.11)
    - mọi chuỗi frontend (xem [`frontend.md`](frontend.md) §5)
    - giữ nguyên placeholder: `"Too many registration attempts. Please try again in :seconds seconds."` → `"Bạn thử đăng ký quá nhiều lần. Vui lòng thử lại sau :seconds giây."`

### 5.9. Dịch chuỗi PHP hiện có

| Vị trí                                        | Cách sửa                                                                                                                                                                            |
| --------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Flash trong controller                        | `->with('status', __('Account updated.'))`, tương tự cho mọi flash ở §0.                                                                                                            |
| `CategoryInUseException`                      | `parent::__construct(__('This category still has transactions and cannot be deleted.'))` — controller giữ `$exception->getMessage()`.                                               |
| `messages()` của Category                     | `'name.unique' => __('You already have a category with this name and type.')`                                                                                                       |
| `UpdateCategoryRequest::after()`              | bọc 2 message bằng `__()`.                                                                                                                                                          |
| `ValidEmail`, `ValidPassword`                 | `$fail(__('The :attribute field must be a valid email address.'))` — `__()` không có replacement nên giữ nguyên `:attribute`; validator thay bằng tên field đã dịch (`attributes`). |
| `AppServiceProvider::configureRateLimiting()` | call site truyền chuỗi **đã dịch**: `$this->throttledResponse(__('Too many registration attempts. Please try again in :seconds seconds.'))`, login truyền `__('auth.throttle')`.    |
| `AppServiceProvider::throttledResponse()`     | nhận template đã dịch, thay placeholder bằng `strtr($message, [':seconds' => …, ':minutes' => …])` thay vì `__($message, …)`.                                                       |

Callback của `RateLimiter::for()` chạy **mỗi request**, sau `SetLocale` → `__()` ở call site ra đúng ngôn ngữ.

Mọi key literal phải nằm trong `__('…')` (hoặc `trans('…')`) để `lang:check` thấy. Không truyền biến vào `__()` trừ khi giá trị đó chắc chắn đã có trong `vi.json` qua một literal khác.

### 5.10. `app/Enums/Concerns/HasOptions.php` và `label()` của enum

Giữ nguyên. Nhãn enum hiện do frontend sở hữu (Categories BE Q15); nếu sau này backend gửi `options()`, `label()` đã đi qua `__()` sẵn. `lang:check` **không** quét `self::translate('…')` → nếu có caller, đổi sang `__()` trực tiếp.

### 5.11. `app/Services/UserOnboardingService.php`

```php
/** Translation keys; stored in the locale active at registration (plan §9.3). */
public const DEFAULT_CATEGORIES = [
    'income' => ['Salary', 'Bonus', 'Other income'],
    'expense' => ['Food & Drinks', 'Transportation', 'Shopping', 'Housing', 'Bills', 'Entertainment', 'Health', 'Other expenses'],
];

public const DEFAULT_WALLET_NAME = 'Bank';
```

- `createDefaults()` tạo bằng `__($key)`: `['name' => __(self::DEFAULT_WALLET_NAME)]`, `['name' => __($name), …]`. Vẫn idempotent (`firstOrCreate` theo tên đã dịch).
- Hằng số không bọc được bằng `__()` (biểu thức const), nên `lang:check` không tự thấy 12 key này → liệt kê chúng trong `EXTRA_KEYS` của `scripts/lang-check.mjs` (kèm comment trỏ về file này); `LangFilesTest` cũng kiểm tra chúng có trong `vi.json`.
- Test hiện có `test_registration_creates_default_wallet_and_categories` so với hằng số → chạy ở `en` nên vẫn pass không cần sửa.

### 5.12. `database/seeders/DemoDataSeeder.php`

- Đầu `run()`: `app()->setLocale('vi')` — dữ liệu demo luôn tiếng Việt (Q11), bất kể `APP_LOCALE`.
- Tra ví ngân hàng: `->where('name', __(UserOnboardingService::DEFAULT_WALLET_NAME))`.
- Các tên danh mục đang tra bằng tiếng Việt (`keyBy('name')`) giữ nguyên.

### 5.13. `scripts/lang-check.mjs` + `package.json` + CI

Script Node thuần (không dependency), chạy bằng `npm run lang:check` (`"lang:check": "node scripts/lang-check.mjs"`).

1. Thu thập key đã dùng:
    - `resources/js/**/*.{ts,tsx}`: literal trong `t('…')`, `t("…")`, `trans('…')`, `trans("…")` (có xử lý ký tự escape).
    - `app/**/*.php`: literal trong `__('…')`, `__("…")`, `trans('…')`.
    - Cộng `EXTRA_KEYS` (tên seed §5.11).
2. Bỏ qua key dạng group: `/^[a-z_]+(\.[a-z_]+)+$/` (`auth.failed`, `validation.unique`) — thuộc file PHP.
3. So với `lang/vi.json`:
    - key dùng mà **thiếu** trong `vi.json` → in danh sách, `exit 1`.
    - key trong `vi.json` mà **không** được dùng → in cảnh báo, không fail (có thể là key động).
    - `vi.json` có giá trị rỗng → `exit 1`.
4. `.github/workflows/linter.yml`: thêm bước `Lang Check` → `npm run lang:check` sau "Prettier Check".

Giới hạn đã biết: key truyền qua biến (`t(label)`) không bị bắt — vì vậy mọi map nhãn phía frontend dùng marker `trans()` (frontend §4.2).

---

## 6. Checklist test

### `tests/Feature/LocaleTest.php`

- [x] Không cookie → shared prop `locale` = `config('app.locale')` (test đặt `config(['app.locale' => 'vi'])` rồi assert `vi`).
- [x] Cookie `locale=en` → `locale = 'en'`, `translations = []`.
- [x] Cookie `locale=vi` → `translations` bằng nội dung `lang/vi.json` (assert một key đại diện, ví dụ `'Account updated.'`).
- [x] Cookie `locale=fr` → rơi về mặc định.
- [x] `PUT /locale` `{locale: 'en'}` với **khách** → redirect back, `assertCookie('locale', 'en')`.
- [x] `PUT /locale` với **user đã đăng nhập** → như trên, vẫn đăng nhập.
- [x] `PUT /locale` `{locale: 'fr'}` / thiếu `locale` → lỗi trên `locale`, không set cookie.
- [x] Cookie còn sau logout: đặt `locale=en`, `POST /logout`, `GET /login` với cookie đó → `locale = 'en'`.
- [x] Once prop: request có header `X-Inertia-Except-Once-Props: translations.vi` và cookie `vi` → response **không** chứa `translations`; cùng header nhưng cookie `en` → **có** `translations`.
- [x] Flash tiếng Việt: cookie `vi`, `PATCH /account` → `flash.status = 'Đã cập nhật tài khoản.'`.
- [x] Validation tiếng Việt: cookie `vi`, `POST /register` rỗng → lỗi `name` = "Trường tên là bắt buộc.".
- [x] Rule tự viết tiếng Việt: cookie `vi`, email sai định dạng → message của `ValidEmail` bản vi, `:attribute` đã được thay.
- [x] Throttle tiếng Việt: cookie `vi`, vượt limit `register` → message vi có số giây.
- [x] Đăng ký với cookie `vi` → ví "Ngân hàng" + 11 danh mục tiếng Việt; với cookie `en` → "Bank" + tên tiếng Anh.
- [x] `<html lang>` trong response HTML đầu tiên khớp cookie (`vi` / `en`).

### `tests/Unit/LangFilesTest.php`

- [x] `lang/vi.json` là JSON hợp lệ, mọi giá trị là chuỗi khác rỗng, key đã sắp xếp.
- [x] `lang/vi/validation.php` có **đủ** mọi key (đệ quy) của `lang/en/validation.php`.
- [x] Tương tự cho `auth.php`, `pagination.php`, `passwords.php`.
- [x] Mọi key trong `UserOnboardingService::DEFAULT_CATEGORIES` và `DEFAULT_WALLET_NAME` có trong `vi.json`.

### Hồi quy

- [x] Toàn bộ test hiện có pass **không sửa** (chạy `en` nhờ `phpunit.xml`).

---

## 7. Trình tự triển khai

1. `php artisan lang:publish`; sửa `config/app.php`, `.env.example`, `phpunit.xml`.
2. `Locale` enum → `LocaleService` → `SetLocale` (+ `bootstrap/app.php`) → `UpdateLocaleRequest` → `LocaleController` → route.
3. Impact analysis → `HandleInertiaRequests` (`locale`, `shareOnce`).
4. Impact analysis cho từng symbol ở §5.9, §5.11 (`createDefaults`, `throttledResponse`, `CategoryInUseException`…) → bọc `__()`.
5. `DemoDataSeeder`.
6. Viết `lang/vi/*.php`, rồi `lang/vi.json` phần backend (frontend bổ sung phần của mình).
7. `scripts/lang-check.mjs` + `package.json` + CI.
8. Test §6.
9. `./vendor/bin/pint` → `composer analyse` → `php artisan test` → `npm run lang:check`.
10. Trước commit: `node .gitnexus/run.cjs detect-changes --scope all --repo .`.

---

## 8. Đã cân nhắc và cố ý bỏ

| Bỏ qua                                    | Lý do                                                                            |
| ----------------------------------------- | -------------------------------------------------------------------------------- |
| Cột `users.locale`                        | Không cần đồng bộ giữa thiết bị (Q2).                                            |
| Đọc `Accept-Language`                     | Mặc định cố định dễ đoán, dễ test (Q10).                                         |
| react-i18next / `laravel-react-i18n`      | Hai nguồn bản dịch hoặc thêm dependency (Q3).                                    |
| Key có cấu trúc (`categories.form.title`) | Cần thêm `en.json`, và backend vốn đã viết câu tiếng Anh (Q7).                   |
| Package `laravel-lang/lang`               | Tự dịch một lần đủ dùng, không thêm dependency (Q16).                            |
| Tiền tố ngôn ngữ trên URL                 | Phải đổi toàn bộ route.                                                          |
| Sửa test cũ sang tiếng Việt               | Test nghiệp vụ không nên gãy khi sửa câu chữ (Q13).                              |
| Throttle cho `PUT /locale`                | Không có tác dụng phụ đáng kể; chỉ set cookie.                                   |
| Dịch lại dữ liệu seed khi đổi ngôn ngữ    | Là dữ liệu người dùng; làm hỏng unique `(user_id, name, type)` và đổi tên (Q11). |

---

## 9. Follow-up

- Nếu backend bắt đầu gửi `options()` của enum: đổi `HasOptions::translate()` sang gọi `__()` trực tiếp ở từng case để `lang:check` thấy key.
- Email / notification đa ngôn ngữ khi Phase sau có gửi mail (dùng `Localizable` / `locale()` của Mailable).
- Cache dictionary theo `filemtime` nếu `vi.json` lớn.
