# Task — Wallets (Backend)

> Nguồn: [`features/phase-1.md`](../../features/phase-1.md) §4 + [`phases/phase-1.md`](../../phases/phase-1.md) §3.2, §4.1–§4.3 + buổi grill Q1–Q15.
> Phạm vi: **chỉ backend**. Inertia page và component React là task riêng ([`frontend.md`](frontend.md), chưa viết).
> Nhánh: `feat/implement-wallets-backend` tách từ `main`, **sau khi** spec này (nhánh `feat/create-spec-wallets`) đã merge. Một nhánh, **mỗi task một commit**, mở một PR khi xong cả 6 task (Q4, Q14).

---

## 0. Tình trạng hiện tại

Tầng schema **đã xong**, các task dưới đây không đụng tới migration.

| Thành phần                                                        | Trạng thái                                                                                |
| ----------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| `database/migrations/2026_09_19_000002_create_wallets_table.php`  | ✅ có: `UNIQUE(user_id, name)`, `INDEX(user_id, status)`, CHECK `initial_balance >= 0`    |
| `app/Models/Wallet.php`                                           | ✅ có: casts enum + `decimal:4`, `user()`, `currency()`, `transactions()`, default status |
| `app/Models/Currency.php`                                         | ✅ có: PK `code`, `decimal_places`, `is_active`                                           |
| `app/Enums/WalletType.php`, `app/Enums/EntityStatus.php`          | ✅ có: `EntityStatus::isEditable()` dùng cho guard archived                               |
| `database/factories/WalletFactory.php`                            | ✅ có: states `currency($code)`, `inactive()`, `archived()`                               |
| `database/factories/TransactionFactory.php`                       | ✅ có: states `income()`, `expense()`                                                     |
| `app/Services/UserOnboardingService.php`                          | ✅ có: tạo ví mặc định khi đăng ký                                                        |
| `flash.error` trong `HandleInertiaRequests`                       | ✅ có (thêm ở Categories)                                                                 |
| Scope / Rule / FormRequest / Service / Controller / Routes / Test | ❌ **6 task dưới đây**                                                                    |

Ràng buộc môi trường đã xác minh:

- Collation `utf8mb4_unicode_ci` → `"tien mat"` = `"Tiền Mặt"` trong unique constraint.
- MySQL cho cả dev lẫn test; `tests/TestCase.php` tự seed `CurrencySeeder` (10 currency, VND/JPY/KRW có `decimal_places = 0`).
- `ext-bcmath` đã bật: so sánh số tiền dạng chuỗi bằng `bccomp()`.
- Inertia `testing.ensure_pages_exist = true`: test assert bằng `->component('Wallets/Index', false)` vì page chưa tồn tại (khuôn Categories).
- `phpunit.xml` đặt `APP_LOCALE=en`: test assert message tiếng Anh.

---

## 1. Bảng quyết định (Q1–Q15)

| #   | Quyết định                                                                                                                                                                                                |
| --- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Q1  | Phạm vi: danh sách kèm số dư, tạo, sửa, đổi trạng thái, xóa. Feature §4.7–§4.9 (chọn ví khi tạo giao dịch, lọc và lịch sử giao dịch theo ví) thuộc **Transactions**. Không có `GET /wallets/{wallet}`.    |
| Q2  | Chia task **theo endpoint**: mỗi task là một lát dọc FormRequest → Service → Controller → route → test, review độc lập được.                                                                              |
| Q3  | Một file spec này: phần chung ở đầu, Task 1–6 ở §6, mỗi task tự đủ.                                                                                                                                       |
| Q4  | Một nhánh, mỗi task **một commit**, một PR cuối cùng.                                                                                                                                                     |
| Q5  | Số dư hiện tại là local scope **`Wallet::withCurrentBalance()`** (subquery SQL). Dashboard và form Transactions sẽ dùng lại.                                                                              |
| Q6  | Index chỉ có filter `include_archived` (bật → hiện **cả 3** trạng thái). Thứ tự `created_at ASC, id ASC`.                                                                                                 |
| Q7  | **Không** chặn archive/xóa ví active cuối cùng. Form Transactions sau này tự hiện trạng thái trống.                                                                                                       |
| Q8  | Exception riêng `App\Exceptions\WalletInUseException`. Không gộp với `CategoryInUseException`, không sửa code Categories.                                                                                 |
| Q9  | Props mỗi ví có `transactions_count` và `created_at` dạng `Y-m-d` đã format ở backend. Không gửi nhãn type/status.                                                                                        |
| Q10 | Prop `currencies` chứa **tất cả** currency kèm `is_active`. Frontend tự lọc active cho ô chọn và tra theo `currency_code` khi format.                                                                     |
| Q11 | `POST /wallets`: `name`, `type`, `currency_code`, `initial_balance` đều `required`; `description` nullable ≤ 255; `status` bị bỏ qua (ví mới luôn `active`).                                              |
| Q12 | `initial_balance`: `numeric`, `min:0`, `max:999999999999` + rule class **`App\Rules\MoneyPrecision`** (số chữ số thập phân ≤ `decimal_places`). Transactions dùng lại cho `amount`.                       |
| Q13 | `PATCH /wallets/{wallet}` luôn nhận đủ form. Guard trong `after()`: archived → 422 `status`; ví đã có giao dịch mà **thật sự đổi** `currency_code` hoặc `initial_balance` (`bccomp`) → 422 trên field đó. |
| Q14 | Spec merge trước trên `feat/create-spec-wallets`; code trên `feat/implement-wallets-backend`.                                                                                                             |
| Q15 | 6 task: T1 scope số dư · T2 danh sách · T3 tạo + `MoneyPrecision` · T4 sửa + `ResolvesWallet` · T5 đổi trạng thái · T6 xóa + README.                                                                      |

---

## 2. Routes

Thêm vào group `['auth', 'auth.session']` có sẵn trong `routes/web.php`, ngay dưới khối `categories`. Mỗi task thêm đúng route của mình.

```php
Route::get('/wallets', [WalletController::class, 'index'])->name('wallets.index');                       // T2
Route::post('/wallets', [WalletController::class, 'store'])->name('wallets.store');                       // T3
Route::patch('/wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');           // T4
Route::patch('/wallets/{wallet}/status', [WalletController::class, 'updateStatus'])
    ->name('wallets.status.update');                                                                    // T5
Route::delete('/wallets/{wallet}', [WalletController::class, 'destroy'])->name('wallets.destroy');        // T6
```

`{wallet}` là **id thô**, không dùng route model binding. Không throttle.

---

## 3. Contract của props (Inertia)

Component: `Wallets/Index`

```ts
{
    wallets: Array<{
        id: number;
        name: string;
        type: 'cash' | 'bank' | 'e_wallet' | 'credit_card' | 'other';
        currency_code: string;
        initial_balance: string; // "1000000.0000"
        current_balance: string; // "-250000.0000", có thể âm
        description: string | null;
        status: 'active' | 'inactive' | 'archived';
        transactions_count: number;
        created_at: string; // "2026-10-02", ngày theo Asia/Ho_Chi_Minh
    }>;
    currencies: Array<{
        code: string;
        name: string;
        symbol: string;
        decimal_places: number;
        is_active: boolean;
    }>;
    filters: {
        include_archived: boolean;
    }
}
```

Quy tắc:

- Thứ tự `wallets`: **`created_at ASC, id ASC`** (plan §4.3; `id` phân định hai ví cùng giây).
- Thứ tự `currencies`: `code ASC`. Frontend chọn sẵn `VND` khi tạo ví.
- Tiền luôn là **string** (`decimal:4`), frontend không parse sang `number` để tính.
- ⚠️ `initial_balance` trong props luôn có 4 chữ số thập phân, còn `MoneyPrecision` (T3) đếm chữ số thập phân **viết ra**. Gửi lại nguyên prop (`"1000.0000"` cho VND, `"10.5000"` cho USD) sẽ bị 422; form Sửa phải cắt theo `decimal_places` trước khi gửi (`toAmountInput` trong [`frontend.md`](frontend.md)).
- `transactions_count` đếm mọi giao dịch của ví, không lọc ngày.
- **Không** gửi `user_id`, `updated_at`, nhãn type/status, hay `created_at` dạng timestamp.

---

## 4. Message và bản dịch

Mọi chuỗi đi qua `__()`; mỗi task thêm key của mình vào `lang/vi.json` (giữ thứ tự alphabet) để `npm run lang:check` pass.

| Key (English)                                                                            | Tiếng Việt                                                              | Task |
| ---------------------------------------------------------------------------------------- | ----------------------------------------------------------------------- | ---- |
| `Wallet created.`                                                                        | `Đã tạo ví.`                                                            | T3   |
| `You already have a wallet with this name.`                                              | `Bạn đã có ví với tên này.`                                             | T3   |
| `The selected currency is not available.`                                                | `Tiền tệ đã chọn không còn được hỗ trợ.`                                | T3   |
| `The :attribute must be a whole number for :currency.`                                   | `:Attribute phải là số nguyên với :currency.`                           | T3   |
| `The :attribute may have at most :places decimal places for :currency.`                  | `:Attribute chỉ được có tối đa :places chữ số thập phân với :currency.` | T3   |
| `Wallet updated.`                                                                        | `Đã cập nhật ví.`                                                       | T4   |
| `An archived wallet must be restored before it can be edited.`                           | `Bạn cần khôi phục ví đã lưu trữ trước khi sửa.`                        | T4   |
| `This wallet already has transactions, so its currency can no longer be changed.`        | `Ví đã có giao dịch nên không thể đổi tiền tệ.`                         | T4   |
| `This wallet already has transactions, so its initial balance can no longer be changed.` | `Ví đã có giao dịch nên không thể đổi số dư ban đầu.`                   | T4   |
| `Wallet status updated.`                                                                 | `Đã cập nhật trạng thái ví.`                                            | T5   |
| `Wallet deleted.`                                                                        | `Đã xóa ví.`                                                            | T6   |
| `This wallet still has transactions and cannot be deleted.`                              | `Không thể xóa ví đã có giao dịch.`                                     | T6   |

`lang/vi/validation.php` → `attributes` (T3): `currency_code` → `tiền tệ`, `initial_balance` → `số dư ban đầu`, `description` → `mô tả`.

---

## 5. Quy ước chung cho mọi task

- Trước khi sửa symbol đã tồn tại (`Wallet`, `routes/web.php`…), chạy impact theo `CLAUDE.md`:
  `node .gitnexus/run.cjs impact "<symbol>" --direction upstream --repo .`
- Mỗi commit phải tự pass:
  `./vendor/bin/pint` → `./vendor/bin/phpstan analyse` → `php artisan test` → `npm run lang:check`
- Trước khi commit: `node .gitnexus/run.cjs detect-changes --scope all --repo .`
- Docblock đủ `@param`/`@return` có mô tả, constructor injection `protected` (rule `backend.md`).
- Controller chỉ gọi service với `$request->validated()`; ownership resolve qua `$request->user()->wallets()` → 404 (rule `backend-http.md`).

---

## 6. Tasks

### Task 1 — Scope số dư hiện tại

**Commit:** `feat(wallets): add withCurrentBalance scope`
**Phụ thuộc:** —

**File**

```
app/Models/Wallet.php                 (sửa: + scope, + @property-read)
tests/Unit/Models/WalletTest.php      (mới)
```

**Đặc tả**

Local scope theo cú pháp attribute `#[Scope]` của Laravel, thêm cột `current_balance` bằng **một** biểu thức SQL:

```text
current_balance = wallets.initial_balance
                + COALESCE((SELECT SUM(CASE WHEN transactions.type = 'income' THEN amount
                                            WHEN transactions.type = 'expense' THEN -amount END)
                            FROM transactions
                            WHERE transactions.wallet_id = wallets.id), 0)
```

```php
/**
 * Add the current balance, derived from the initial balance and the
 * wallet's transactions, as a decimal string column.
 *
 * @param  Builder<Wallet>  $query  The wallet query to add the column to.
 */
#[Scope]
protected function withCurrentBalance(Builder $query): void
{
    // selectRaw() alone would replace the default "select *".
    if ($query->getQuery()->columns === null) {
        $query->select($query->qualifyColumn('*'));
    }

    $query->selectRaw('...', [TransactionType::Income->value, TransactionType::Expense->value])
        ->withCasts(['current_balance' => 'decimal:4']);
}
```

- Giá trị type lấy từ `TransactionType`, truyền qua binding, không viết cứng trong chuỗi SQL.
- ⚠️ **Phải ghi rõ tên bảng** `transactions.type`: cả `wallets` lẫn `transactions` đều có cột `type`.
- ⚠️ `selectRaw()` **không** tự thêm `wallets.*` (chỉ `addSelect()` với subquery có key mới làm vậy). Thiếu bước `select(wallets.*)` ở trên thì model chỉ còn mỗi `current_balance`; test bắt lỗi này bằng cách kiểm tra các cột gốc vẫn có mặt.
- Mọi trạng thái ví và mọi giao dịch đều được tính (plan §4.2: "tính vào số dư ✅" ở cả 3 trạng thái).
- Ghép được với `withCount('transactions')` trong cùng query (T2 dùng cả hai).
- Thêm vào docblock class `Wallet`: `@property-read string $current_balance` và `@property-read int $transactions_count` (chỉ có khi gọi scope / `withCount`), để PHPStan chấp nhận `$wallet->current_balance` trong `listFor()` ở T2.

**Test** (`WalletTest`, `RefreshDatabase`)

- [ ] Ví chưa có giao dịch → `current_balance` = `initial_balance` (`"1000.0000"`).
- [ ] Thu 300 + chi 100 trên ví `initial_balance = 1000` → `"1200.0000"`.
- [ ] Chi vượt số dư → số âm (`"-500.0000"`). _(plan §8: "Chi vượt số dư: được phép, ví âm")_
- [ ] Giao dịch của **ví khác** (kể cả của cùng user) không bị cộng vào.
- [ ] Ví `archived` vẫn tính đúng số dư.
- [ ] Giá trị trả về là `string` có đúng 4 chữ số thập phân.
- [ ] Các cột gốc (`name`, `initial_balance`…) vẫn có mặt khi chỉ gọi scope.

---

### Task 2 — Danh sách ví

**Commit:** `feat(wallets): list wallets with current balance`
**Phụ thuộc:** T1

**File**

```
app/Http/Requests/Wallet/IndexWalletRequest.php   (mới)
app/Services/WalletService.php                    (mới)
app/Http/Controllers/WalletController.php         (mới)
routes/web.php                                    (sửa: + wallets.index)
tests/Feature/Wallet/IndexWalletTest.php          (mới)
```

**`IndexWalletRequest`**

| Field              | Rules                 |
| ------------------ | --------------------- |
| `include_archived` | `nullable`, `boolean` |

**`WalletService`** (đọc lẫn ghi, khuôn `CategoryService`)

```php
/** @return array{include_archived: bool} */
public function normalizeFilters(array $filters): array;

/** @return Collection<int, array<string, mixed>> Whitelisted fields only, ordered created_at ASC, id ASC. */
public function listFor(User $user, array $filters): Collection;

/** @return EloquentCollection<int, Currency> Every currency, active or not, ordered by code; only the 5 columns of §3. */
public function currencies(): EloquentCollection;
```

- `listFor()`: `$user->wallets()->withCurrentBalance()->withCount('transactions')`, ẩn `archived` khi `include_archived` tắt, rồi `->map(fn (Wallet $wallet) => [...])` trả **đúng** các field ở §3. `created_at` → `$wallet->created_at->format('Y-m-d')` (app timezone đã là `Asia/Ho_Chi_Minh`).
- `currencies()`: `Currency::query()->orderBy('code')->get(['code', 'name', 'symbol', 'decimal_places', 'is_active'])`. `get()` trả `Illuminate\Database\Eloquent\Collection<int, Currency>` (import alias `EloquentCollection`), **không** phải `Support\Collection` của mảng: khai báo sai kiểu thì PHPStan level 6 báo lỗi. Model chỉ có 5 cột đã chọn nên Inertia serialize ra đúng 5 field, casts `is_active`/`decimal_places` vẫn áp dụng.

**`WalletController::index`**

```php
$filters = $this->walletService->normalizeFilters($request->validated());

return Inertia::render('Wallets/Index', [
    'wallets' => $this->walletService->listFor($request->user(), $filters),
    'currencies' => $this->walletService->currencies(),
    'filters' => $filters,
]);
```

**Test** (`IndexWalletTest`)

- [ ] Guest → redirect `/login`.
- [ ] Render component `Wallets/Index` (`->component('Wallets/Index', false)`).
- [ ] Mỗi ví chỉ có đúng 10 field ở §3, assert trong `has()` có scope: **fail nếu lộ `user_id` hoặc `updated_at`**.
- [ ] `current_balance` và `transactions_count` đúng khi ví có cả thu lẫn chi.
- [ ] Mặc định **ẩn** ví `archived`; `active` và `inactive` đều hiện.
- [ ] `?include_archived=1` → hiện cả 3 trạng thái; `filters.include_archived` = `true`.
- [ ] Thứ tự `created_at ASC`: tạo ví theo thứ tự thời gian khác thứ tự tên (`travelTo`).
- [ ] Hai ví cùng `created_at` → `id ASC`.
- [ ] `created_at` ra `Y-m-d` theo giờ VN: ví tạo lúc `2026-10-02 00:30` (giờ VN, tức `2026-10-01` theo UTC) → `"2026-10-02"`.
- [ ] Không thấy ví của user khác.
- [ ] `currencies` có đủ 10 currency, **kể cả** currency `is_active = false` (tắt `USD` trong test), mỗi phần tử đúng 5 field. ⚠️ Ví trong test này phải tạo bằng `Wallet::factory()->currency('VND')`: factory mặc định tạo thêm currency `9##` qua `Currency::factory()` (cả `TransactionFactory` cũng vậy), làm số lượng vượt 10.
- [ ] `?include_archived=abc` → 422.

---

### Task 3 — Tạo ví

**Commit:** `feat(wallets): create wallet`
**Phụ thuộc:** T2

**File**

```
app/Rules/MoneyPrecision.php                      (mới; thư mục mới)
app/Http/Requests/Wallet/StoreWalletRequest.php   (mới)
app/Services/WalletService.php                    (sửa: + create)
app/Http/Controllers/WalletController.php         (sửa: + store)
routes/web.php                                    (sửa: + wallets.store)
lang/vi.json, lang/vi/validation.php              (sửa: key T3, attributes)
tests/Unit/Rules/MoneyPrecisionTest.php           (mới)
tests/Feature/Wallet/StoreWalletTest.php          (mới)
```

**`App\Rules\MoneyPrecision`**

```php
class MoneyPrecision implements ValidationRule
{
    /**
     * @param  Currency  $currency  The currency whose decimal_places caps the value's precision.
     */
    public function __construct(protected Currency $currency) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void;
}
```

- Thứ tự kiểm tra trong `validate()`:
    1. Không phải `string`/`int`/`float`, không `is_numeric()`, hoặc **âm** → `return` không báo lỗi: đó là việc của `numeric`/`min:0`/`required`, tránh hai lỗi chồng nhau trên field và tránh `preg_match()` nhận mảng (`TypeError` → 500).
    2. Còn lại phải khớp `^\d+(\.\d+)?$` trên `(string) $value`; dạng số hợp lệ khác (`"1e3"`, `"10."`, `".5"`, float JSON ra `"1.0E-5"`) fail với cùng message precision.
- Đếm **chữ số thập phân viết ra** (giống rule `decimal` của Laravel): `"10.50"` USD hợp lệ, `"10.500"` USD bị từ chối, `"10.0"` VND bị từ chối.
- Message theo `decimal_places`:
    - `0` → `$fail('The :attribute must be a whole number for :currency.')->translate(['currency' => $code])`
    - `N > 0` → `$fail('The :attribute may have at most :places decimal places for :currency.')->translate([...])`

**`StoreWalletRequest`**

`prepareForValidation()`, mỗi field sau guard `is_string()` riêng (khuôn `StoreCategoryRequest`):

- `name` → `Str::squish`.
- `currency_code` → `Str::upper`. ⚠️ Collation `utf8mb4_unicode_ci` khiến cả rule `exists` lẫn FK chấp nhận `"vnd"`; không chuẩn hóa thì ví lưu `"vnd"`, frontend tra currency theo `currency_code` không ra, và guard so sánh `!==` ở T4 coi là đổi currency.

| Field             | Rules                                                                                                       |
| ----------------- | ----------------------------------------------------------------------------------------------------------- |
| `name`            | `required`, `string`, `max:100`, `Rule::unique('wallets')->where('user_id', $this->user()->id)`             |
| `type`            | `required`, `Rule::enum(WalletType::class)`                                                                 |
| `currency_code`   | `required`, `string`, `Rule::exists('currencies', 'code')->where('is_active', true)`                        |
| `initial_balance` | `required`, `numeric`, `min:0`, `max:999999999999`, + `new MoneyPrecision($currency)` khi tìm được currency |
| `description`     | `nullable`, `string`, `max:255`                                                                             |

- `$currency = is_string($code) ? Currency::find($code) : null`: guard `is_string` vì `find()` nhận mảng sẽ trả Collection. Không tìm thấy → bỏ `MoneyPrecision`, lỗi đã nằm ở `currency_code`.
- `messages()`: `name.unique` → `You already have a wallet with this name.`; `currency_code.exists` → `The selected currency is not available.`
- `status` không có trong rules → bị loại khỏi `validated()`, model đặt `active`.

**`WalletService::create(User $user, array $data): Wallet`** đi qua `$user->wallets()->create($data)`, `user_id` không bao giờ đến từ request.

**`WalletController::store`** → `back()->with('status', __('Wallet created.'))`.

**Test**

`MoneyPrecisionTest` (dùng `new Currency([...])`, không cần DB):

- [ ] VND (`0`): `"100"` pass; `"100.5"` và `"100.0"` fail với message "whole number".
- [ ] USD (`2`): `"10"`, `"10.5"`, `"10.50"` pass; `"10.505"` fail với message "at most 2 decimal places".
- [ ] `"1e3"` fail.
- [ ] `"-5"`, `"abc"`, `['1']` → rule **không** báo lỗi (để `min:0`/`numeric` báo) và không ném exception.
- [ ] Message có tên currency (`VND`, `USD`).

`StoreWalletTest`:

- [ ] Tạo thành công: đúng `user_id`, `status = active`, redirect back, flash `status`.
- [ ] Payload có `status=archived` → bị bỏ qua, vẫn `active`.
- [ ] Payload có `user_id` của người khác → bị bỏ qua.
- [ ] `name` nhiều khoảng trắng giữa → bị squish.
- [ ] Trùng tên ví của mình → 422 `name`; trùng với ví **archived** → 422.
- [ ] `"tien mat"` khi đã có `"Tiền Mặt"` → 422 (collation). _(plan §8)_
- [ ] Trùng tên ví của **user khác** → thành công.
- [ ] `name` 101 ký tự → 422.
- [ ] `type` thiếu/không hợp lệ → 422.
- [ ] `currency_code` không tồn tại → 422; currency `is_active = false` → 422 với message "not available".
- [ ] `currency_code = "usd"` → tạo thành công, lưu `"USD"`.
- [ ] `initial_balance`: thiếu → 422; âm → 422; `1000000000000` → 422; `0` → hợp lệ.
- [ ] `initial_balance = 10.5` với VND → 422; `10.50` với USD → hợp lệ. _(plan §8)_
- [ ] `description` 256 ký tự → 422; bỏ trống → lưu `null`.
- [ ] Guest → redirect `/login`.
- [ ] Message `name.unique` hiện tiếng Việt khi gửi cookie `locale=vi`.

---

### Task 4 — Sửa ví

**Commit:** `feat(wallets): update wallet details`
**Phụ thuộc:** T3 (`MoneyPrecision`)

**File**

```
app/Http/Requests/Concerns/ResolvesWallet.php     (mới)
app/Http/Requests/Wallet/UpdateWalletRequest.php  (mới)
app/Services/WalletService.php                    (sửa: + update)
app/Http/Controllers/WalletController.php         (sửa: + update)
routes/web.php                                    (sửa: + wallets.update)
lang/vi.json                                      (sửa: key T4)
tests/Feature/Wallet/UpdateWalletTest.php         (mới)
```

**`ResolvesWallet`**: chép khuôn `ResolvesCategory`; `wallet(): Wallet` resolve `$this->user()->wallets()->findOrFail($this->route('wallet'))`, memoize trong `$resolvedWallet`.

**`UpdateWalletRequest`**: `use ResolvesWallet;`, `prepareForValidation()` như T3 (squish `name`, upper `currency_code`).

| Field             | Rules                                                                                                                                           |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `name`            | như T3 + `->ignore($this->wallet()->id)`                                                                                                        |
| `type`            | `required`, `Rule::enum(WalletType::class)`; đổi được bất cứ lúc nào                                                                            |
| `currency_code`   | `required`, `string`, `Rule::exists('currencies', 'code')`, thêm `->where('is_active', true)` **chỉ khi** giá trị khác `currency_code` hiện tại |
| `initial_balance` | như T3, `MoneyPrecision` dùng currency **được gửi lên**                                                                                         |
| `description`     | `nullable`, `string`, `max:255`                                                                                                                 |

Guard trong `after()`:

```php
function (Validator $validator): void {
    $wallet = $this->wallet();

    // An archived wallet is read-only until it is restored.
    if (! $wallet->status->isEditable()) {
        $validator->errors()->add('status', __('An archived wallet must be restored before it can be edited.'));

        return;
    }

    // Only a real change is worth a database round trip; skip fields
    // that already failed, since bccomp() throws on a non-numeric string.
    // The balance also waits for a valid currency: without one, MoneyPrecision
    // is not attached, so "1e3" would pass every rule and reach bccomp().
    $currencyChanged = ! $validator->errors()->has('currency_code')
        && $this->input('currency_code') !== $wallet->currency_code;
    $balanceChanged = ! $validator->errors()->hasAny(['currency_code', 'initial_balance'])
        && bccomp((string) $this->input('initial_balance'), $wallet->initial_balance, 4) !== 0;

    if (! $currencyChanged && ! $balanceChanged) {
        return;
    }

    if ($wallet->transactions()->exists()) {
        // add the matching error to currency_code and/or initial_balance
    }
},
```

- ⚠️ So sánh **giá trị**, không chặn mù theo `exists()`: ví đã có giao dịch vẫn đổi được tên, loại, mô tả khi gửi lại currency và số dư cũ.
- `"100"` và `"100.0000"` là **bằng nhau** (`bccomp` scale 4), nhưng `"100.0000"` vẫn bị `MoneyPrecision` từ chối với VND/USD trước khi tới guard (xem §3: frontend gửi số dư đã cắt theo `decimal_places`).

**`WalletService::update(Wallet $wallet, array $data): Wallet`**: mỏng, khuôn `CategoryService::update`.

**`WalletController::update`**: lấy model từ `$request->wallet()` → `back()->with('status', __('Wallet updated.'))`.

**Test** (`UpdateWalletTest`)

- [ ] Đổi tên, loại, mô tả thành công.
- [ ] Ví **chưa** có giao dịch: đổi `currency_code` và `initial_balance` thành công.
- [ ] Ví **đã** có giao dịch: đổi `currency_code` → 422 `currency_code`. _(plan §8)_
- [ ] Ví **đã** có giao dịch: đổi `initial_balance` → 422 `initial_balance`. _(plan §8)_
- [ ] Ví **đã** có giao dịch: đổi tên, giữ currency và số dư → thành công.
- [ ] Ví đã có giao dịch, gửi `initial_balance = "1000"` khi DB là `"1000.0000"` → thành công.
- [ ] Ví đang dùng currency đã bị tắt, giữ nguyên currency → thành công; đổi **sang** currency bị tắt → 422.
- [ ] Sửa ví `archived` → 422 `status`; ví `inactive` → thành công.
- [ ] Submit không đổi gì → thành công (unique `ignore` chính nó).
- [ ] Trùng tên một ví khác của mình → 422.
- [ ] Ví VND gửi lại nguyên prop `initial_balance = "1000.0000"` → 422 `initial_balance` (khóa contract §3).
- [ ] Ví đã có giao dịch, `currency_code = "XXX"` + `initial_balance = "1e3"` → 422 `currency_code`, **không** 500 (`bccomp` không được gọi).
- [ ] Ví đã có giao dịch, gửi `currency_code = "vnd"` khi DB là `"VND"` → thành công (không bị coi là đổi currency).
- [ ] Đổi currency USD → VND trong khi `initial_balance = "10.50"` → 422 `initial_balance` (precision theo currency mới).
- [ ] Payload có `status` → bị bỏ qua, trạng thái không đổi.
- [ ] Ví của user khác → **404**.

---

### Task 5 — Đổi trạng thái ví

**Commit:** `feat(wallets): change wallet status`
**Phụ thuộc:** T4 (`ResolvesWallet`)

**File**

```
app/Http/Requests/Wallet/UpdateWalletStatusRequest.php   (mới)
app/Services/WalletService.php                           (sửa: + updateStatus)
app/Http/Controllers/WalletController.php                (sửa: + updateStatus)
routes/web.php                                           (sửa: + wallets.status.update)
lang/vi.json                                             (sửa: key T5)
tests/Feature/Wallet/UpdateWalletStatusTest.php          (mới)
```

**Đặc tả**: chép khuôn `UpdateCategoryStatusRequest`.

| Field    | Rules                                         |
| -------- | --------------------------------------------- |
| `status` | `required`, `Rule::enum(EntityStatus::class)` |

- `authorize()` gọi `$this->wallet()` để ví của user khác ra 404 trước khi validate.
- **Không guard nào** (plan §4.2: chuyển tự do mọi chiều, kể cả khi ví đã có giao dịch; Q7: kể cả ví active cuối cùng).
- Accessor `status(): EntityStatus` giống Categories.
- `WalletService::updateStatus(Wallet $wallet, EntityStatus $status): Wallet` → controller flash `Wallet status updated.`.

**Test** (`UpdateWalletStatusTest`)

- [ ] `active` → `archived`; `archived` → `active`; `archived` → `inactive`.
- [ ] Ví đã có giao dịch vẫn đổi được trạng thái.
- [ ] Archive ví active **duy nhất** của user → thành công (Q7).
- [ ] `status` không hợp lệ → 422.
- [ ] Ví của user khác → **404**.

---

### Task 6 — Xóa ví

**Commit:** `feat(wallets): delete unused wallet`
**Phụ thuộc:** T2

**File**

```
app/Exceptions/WalletInUseException.php     (mới)
app/Services/WalletService.php              (sửa: + delete)
app/Http/Controllers/WalletController.php   (sửa: + destroy)
routes/web.php                              (sửa: + wallets.destroy)
lang/vi.json                                (sửa: key T6)
tests/Feature/Wallet/DeleteWalletTest.php   (mới)
docs/README.md                              (sửa: trạng thái Wallets backend)
```

**Đặc tả**: khuôn `CategoryInUseException` / `CategoryService::delete` / `CategoryController::destroy`.

- `WalletInUseException extends RuntimeException`, nhận `public readonly Wallet $wallet`, message `This wallet still has transactions and cannot be deleted.`.
- `WalletService::delete()` kiểm tra `$wallet->transactions()->exists()` rồi mới xóa. FK `ON DELETE RESTRICT` là lớp bảo vệ cuối.
- `destroy(Request $request, string $wallet)`: resolve qua `$request->user()->wallets()->findOrFail()`, bắt exception → `back()->with('error', ...)`, thành công → `back()->with('status', __('Wallet deleted.'))`.

**Test** (`DeleteWalletTest`)

- [ ] Xóa được ví chưa có giao dịch; row biến mất.
- [ ] Ví đã có giao dịch → redirect back, `flash.error` có nội dung, row **vẫn còn**. _(plan §8)_
- [ ] Ví `archived` chưa có giao dịch → xóa được.
- [ ] Xóa ví active **duy nhất** → thành công (Q7).
- [ ] Ví của user khác → **404**.
- [ ] Redirect giữ query string (`?include_archived=1`).

---

## 7. Đã cân nhắc và cố ý bỏ

| Bỏ qua                                                     | Lý do                                                                                                                  |
| ---------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| `GET /wallets/{wallet}` và lịch sử giao dịch theo ví       | Là danh sách Transactions có filter `wallet_id` (plan §6). Làm ở module Transactions để không viết query hai lần. (Q1) |
| Filter `type` / `currency_code`, phân trang, tìm kiếm      | Mỗi user có vài ví; plan §4.3 không yêu cầu. (Q6)                                                                      |
| Chặn archive/xóa ví active cuối cùng                       | Plan §4.2 cho chuyển trạng thái tự do; user tự khôi phục hoặc tạo ví mới. (Q7)                                         |
| `ResourceInUseException` dùng chung                        | Chỉ khác message và kiểu model; gộp lại kéo code Categories đã merge vào PR này. (Q8)                                  |
| Gửi `has_transactions` thay `transactions_count`           | Count giống Categories và hiển thị được; frontend tự suy ra `> 0`. (Q9)                                                |
| Rule `sometimes` cho currency/số dư khi ví đã có giao dịch | Backend sẽ ngầm chấp nhận payload thiếu field, lệch khuôn guard `type` của Categories. (Q13)                           |
| Bắt `QueryException` 1062 cho unique race                  | Chỉ double-submit mới gây ra, đã bị `LoadingOverlay` + `processing` của Inertia chặn. (tiền lệ Categories Q10)         |
| `WalletPolicy`, route model binding                        | Policy ném 403, trái với "404" ở plan §1; quyền sở hữu đã nằm trong quan hệ Eloquent. (tiền lệ Categories Q5)          |
| Lưu `current_balance` vào bảng                             | Plan §3.2 / §4.1: luôn tính từ `transactions`.                                                                         |
