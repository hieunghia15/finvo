# Task — Categories (Backend)

> Nguồn: [`features/phase-1.md`](../../features/phase-1.md) §3 + [`phases/phase-1.md`](../../phases/phase-1.md) §3.3, §4.2, §4.4 + buổi grill Q1–Q15.
> Phạm vi: **chỉ backend**. Inertia page và component React là task riêng.
> Nhánh đề xuất: `feat/implement-categories-backend`.

---

## 0. Tình trạng hiện tại

Tầng schema **đã xong** ở commit trước, task này không đụng tới migration.

| Thành phần                                                          | Trạng thái                                                              |
| ------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| `database/migrations/2026_09_19_000003_create_categories_table.php` | ✅ có — `UNIQUE(user_id, name, type)`, `INDEX(user_id, type, status)`   |
| `app/Models/Category.php`                                           | ✅ có — casts enum, `user()`, `transactions()`, default `status=active` |
| `app/Enums/TransactionType.php`                                     | ✅ có — `Income`, `Expense`                                             |
| `app/Enums/EntityStatus.php`                                        | ✅ có — `isSelectable()`, `isEditable()`, `isVisibleByDefault()`        |
| `database/factories/CategoryFactory.php`                            | ✅ có — states `income()`, `expense()`, `inactive()`, `archived()`      |
| `app/Services/UserOnboardingService.php`                            | ✅ có — seed 11 danh mục mặc định khi đăng ký                           |
| Controller / FormRequest / Service / Routes / Tests                 | ❌ **task này**                                                         |

Ràng buộc môi trường đã xác minh:

- Collation `utf8mb4_unicode_ci` (`config/database.php:57`) → so sánh **không phân biệt hoa/thường và dấu**. `"luong"` = `"Lương"` trong unique constraint.
- MySQL cho cả dev lẫn test (`phpunit.xml`, DB `finvo_testing`) → dùng được cú pháp riêng của MySQL.
- `tests/TestCase.php` đã tự seed `CurrencySeeder` mỗi lần `RefreshDatabase` chạy.

---

## 1. Bảng quyết định (Q1–Q15)

| #   | Quyết định                                                                                                               |
| --- | ------------------------------------------------------------------------------------------------------------------------ |
| Q1  | CRUD bằng **modal trên trang index**. Không có route `create`/`edit`/`show`.                                             |
| Q2  | Đổi trạng thái đi qua **endpoint riêng** `PATCH /categories/{category}/status`, tách khỏi endpoint sửa name/type.        |
| Q3  | Index **không phân trang, không tìm kiếm**. Query params: `type` (optional) + `include_archived` (bool).                 |
| Q4  | Lỗi gắn được với field → `ValidationException`. Lỗi không gắn field (xóa) → **`flash.error`** (thêm vào shared props).   |
| Q5  | Chặn truy cập chéo user bằng `$request->user()->categories()->findOrFail()` → **404**. Không dùng Policy, không binding. |
| Q6  | `POST /categories` chỉ nhận `name` + `type`. Danh mục mới **luôn `active`** (default của model).                         |
| Q7  | Guard đổi `type` nằm trong `UpdateCategoryRequest`; FormRequest **resolve + memoize** category, controller dùng lại.     |
| Q8  | `include_archived=1` → hiện **tất cả** (active + inactive + archived), không phải chỉ archived.                          |
| Q9  | Props: **mảng phẳng đã sắp xếp** ở SQL. Fields: `id`, `name`, `type`, `status`, `transactions_count`. Kèm `filters`.     |
| Q10 | **Không** xử lý race condition unique (`QueryException` 1062). Cố ý bỏ — xem §8.                                         |
| Q11 | Feature test **đầy đủ cho cả 5 endpoint**, theo khuôn `tests/Feature/Account/`.                                          |
| Q12 | `CategoryService` ôm **cả đọc lẫn ghi**. Không tách Eloquent scope (chưa có call site thứ hai).                          |
| Q13 | Exception **riêng cho Category**: `App\Exceptions\CategoryInUseException`. Không tạo class dùng chung vội.               |
| Q14 | Sau mutation: **`redirect()->back()`** để giữ nguyên query string (filter không bị reset).                               |
| Q15 | Backend giữ chuỗi **tiếng Anh**; **không** gửi `typeOptions`/`statusOptions`. Frontend sở hữu nhãn tiếng Việt.           |

---

## 2. Danh sách file

**Tạo mới**

```
app/Exceptions/CategoryInUseException.php
app/Http/Requests/Category/IndexCategoryRequest.php
app/Http/Requests/Category/StoreCategoryRequest.php
app/Http/Requests/Category/UpdateCategoryRequest.php
app/Http/Requests/Category/UpdateCategoryStatusRequest.php
app/Http/Requests/Concerns/ResolvesCategory.php
app/Services/CategoryService.php
app/Http/Controllers/CategoryController.php
tests/Feature/Category/IndexCategoryTest.php
tests/Feature/Category/StoreCategoryTest.php
tests/Feature/Category/UpdateCategoryTest.php
tests/Feature/Category/UpdateCategoryStatusTest.php
tests/Feature/Category/DeleteCategoryTest.php
```

**Sửa**

```
routes/web.php                              (+5 route)
app/Http/Middleware/HandleInertiaRequests.php  (+ flash.error)
```

**Không đụng tới:** migration, `Category.php`, `TransactionType.php`, `EntityStatus.php`, `CategoryFactory.php`, `UserOnboardingService.php`.

---

## 3. Routes

Thêm vào **trong group `['auth', 'auth.session']` đã có sẵn** của `routes/web.php`:

```php
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::patch('/categories/{category}/status', [CategoryController::class, 'updateStatus'])->name('categories.status.update');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
```

`{category}` là **id thô**, không phải route model binding (Q5). Không thêm throttle.

---

## 4. Contract của props (Inertia)

Component: `Categories/Index`

```ts
{
    categories: Array<{
        id: number;
        name: string;
        type: 'income' | 'expense';
        status: 'active' | 'inactive' | 'archived';
        transactions_count: number;
    }>;
    filters: {
        type: 'income' | 'expense' | null;
        include_archived: boolean;
    }
}
```

Quy tắc, bám đúng [`phases/phase-1.md`](../../phases/phase-1.md) §4.4:

- Thứ tự: **`id DESC`** (mới nhất trước), giống nhau khi có hoặc không có filter `type`.
- `transactions_count` đếm **toàn bộ** giao dịch của danh mục, không lọc theo ngày, tính cả danh mục archived.
- **Không** gửi `user_id`, `created_at`, `updated_at`, `typeOptions`, `statusOptions`.
- `filters` được echo lại nguyên trạng để frontend giữ trạng thái control.

---

## 5. Đặc tả từng file

### 5.1. `app/Exceptions/CategoryInUseException.php`

Thư mục `app/Exceptions/` **chưa tồn tại**, tạo mới.

```php
namespace App\Exceptions;

use App\Models\Category;
use RuntimeException;

class CategoryInUseException extends RuntimeException
{
    public function __construct(public readonly Category $category)
    {
        parent::__construct('This category still has transactions and cannot be deleted.');
    }
}
```

Kế thừa `RuntimeException` chứ không phải `HttpException`: đây là lỗi nghiệp vụ, việc dịch nó sang phản hồi HTTP là việc của controller (Q4/Q13).

### 5.2. `app/Http/Requests/Concerns/ResolvesCategory.php`

Dùng chung bởi `UpdateCategoryRequest` và `UpdateCategoryStatusRequest` (Q7). Đặt trong `Concerns/` theo tiền lệ `app/Enums/Concerns/`.

```php
protected ?Category $resolvedCategory = null;

/**
 * Resolve the route's category through the current user, so another
 * user's row is a 404 rather than a 403. Memoized: rules() and the
 * controller both read it within one request.
 */
public function category(): Category
{
    return $this->resolvedCategory ??= $this->user()
        ->categories()
        ->findOrFail($this->route('category'));
}
```

### 5.3. `app/Http/Requests/Category/IndexCategoryRequest.php`

`backend.md` yêu cầu mọi validation đi qua FormRequest, kể cả query string.

| Field              | Rules                                            |
| ------------------ | ------------------------------------------------ |
| `type`             | `nullable`, `Rule::enum(TransactionType::class)` |
| `include_archived` | `nullable`, `boolean`                            |

Không có accessor: controller truyền thẳng `$request->validated()` sang `CategoryService::normalizeFilters()`.

### 5.4. `app/Http/Requests/Category/StoreCategoryRequest.php`

`prepareForValidation()`: `Str::squish` cho `name` (khuôn có sẵn ở `UpdateAccountRequest`). `TrimStrings` middleware đã cắt đầu/cuối.

| Field  | Rules                                                          |
| ------ | -------------------------------------------------------------- |
| `name` | `required`, `string`, `max:100`, unique theo `(user_id, type)` |
| `type` | `required`, `Rule::enum(TransactionType::class)`               |

```php
Rule::unique('categories')
    ->where('user_id', $this->user()->id)
    ->where('type', $this->input('type'))
```

`status` **không** nằm trong rules → bị FormRequest loại bỏ, model tự đặt `active` (Q6).

`max:100` khớp `VARCHAR(100)` của migration.

### 5.5. `app/Http/Requests/Category/UpdateCategoryRequest.php`

`use ResolvesCategory;` + `prepareForValidation()` squish `name` như trên.

| Field  | Rules                                                                              |
| ------ | ---------------------------------------------------------------------------------- |
| `name` | `required`, `string`, `max:100`, unique theo `(user_id, type)` **`->ignore($id)`** |
| `type` | `required`, `Rule::enum(TransactionType::class)`                                   |

Hai guard trong `after()`:

```php
public function after(): array
{
    return [
        function (Validator $validator): void {
            $category = $this->category();

            // §4.2: an archived category must be restored before its details can be edited.
            if (! $category->status->isEditable()) {
                $validator->errors()->add('status', 'An archived category must be restored before it can be edited.');
                return;
            }

            // §4.4: the type is locked once the category has been used.
            if ($this->enum('type', TransactionType::class) !== $category->type
                && $category->transactions()->exists()) {
                $validator->errors()->add('type', 'This category already has transactions, so its type can no longer be changed.');
            }
        },
    ];
}
```

⚠️ Guard `type` phải **so sánh** type cũ với type mới, không chỉ kiểm tra `exists()`. Đổi tên một danh mục đã có giao dịch mà **giữ nguyên type** là hợp lệ — ma trận §4.2 cho phép sửa thông tin ở trạng thái `active` và `inactive`.

### 5.6. `app/Http/Requests/Category/UpdateCategoryStatusRequest.php`

`use ResolvesCategory;`

| Field    | Rules                                         |
| -------- | --------------------------------------------- |
| `status` | `required`, `Rule::enum(EntityStatus::class)` |

**Không có guard nào.** §4.2: chuyển trạng thái tự do mọi chiều, kể cả khôi phục từ `archived`, kể cả khi danh mục đã có giao dịch. Đây chính là lý do endpoint này tách riêng (Q2).

### 5.7. `app/Services/CategoryService.php`

```php
/**
 * @return array{type: string|null, include_archived: bool}
 */
public function normalizeFilters(array $filters): array;

/**
 * @return Collection<int, array<string, mixed>> Whitelisted fields only.
 */
public function listFor(User $user, array $filters): Collection
{
    ['type' => $type, 'include_archived' => $includeArchived] = $this->normalizeFilters($filters);

    return $user->categories()
        ->withCount('transactions')
        ->when($type, fn ($query) => $query->where('type', $type))
        ->when(! $includeArchived, fn ($query) => $query->whereNot('status', EntityStatus::Archived))
        // Newest first, the same order with or without a type filter.
        ->orderByDesc('id')
        ->get()
        ->map->only(['id', 'name', 'type', 'status', 'transactions_count']);
}

public function create(User $user, array $data): Category;

public function update(Category $category, array $data): Category;

public function updateStatus(Category $category, EntityStatus $status): Category;

/**
 * @throws CategoryInUseException
 */
public function delete(Category $category): void;
```

- `create()` đi qua `$user->categories()->create([...])` để `user_id` không bao giờ đến từ request (plan §4.5).
- `delete()` kiểm tra `$category->transactions()->exists()` rồi mới xóa. FK `ON DELETE RESTRICT` của `transactions.category_id` là lớp bảo vệ cuối, không phải lớp đầu.
- `update()`/`updateStatus()` mỏng là **có chủ đích** (Q12): giữ một điểm vào duy nhất cho mọi thao tác ghi, để Wallets/Transactions đi theo cùng khuôn.

### 5.8. `app/Http/Controllers/CategoryController.php`

Constructor injection `CategoryService` (khuôn `AccountController`). Một controller cho toàn bộ domain Category.

| Method         | Request                        | Trả về                                               |
| -------------- | ------------------------------ | ---------------------------------------------------- |
| `index`        | `IndexCategoryRequest`         | `Inertia::render('Categories/Index', [...])`         |
| `store`        | `StoreCategoryRequest`         | `back()->with('status', 'Category created.')`        |
| `update`       | `UpdateCategoryRequest`        | `back()->with('status', 'Category updated.')`        |
| `updateStatus` | `UpdateCategoryStatusRequest`  | `back()->with('status', 'Category status updated.')` |
| `destroy`      | `Request` + `string $category` | `back()->with('status'\|'error', ...)`               |

`index` — chỉ gọi service; whitelist field và chuẩn hóa filter nằm trong `CategoryService`:

```php
$filters = $this->categoryService->normalizeFilters($request->validated());
$categories = $this->categoryService->listFor($request->user(), $filters);

return Inertia::render('Categories/Index', [
    'categories' => $categories,
    'filters' => $filters,
]);
```

`update`/`updateStatus` — lấy model từ `$request->category()`, **không** resolve lại (Q7).

`destroy` — là method duy nhất không có FormRequest, nên tự resolve:

```php
public function destroy(Request $request, string $category): RedirectResponse
{
    $model = $request->user()->categories()->findOrFail($category);

    try {
        $this->categoryService->delete($model);
    } catch (CategoryInUseException $exception) {
        return back()->with('error', $exception->getMessage());
    }

    return back()->with('status', 'Category deleted.');
}
```

### 5.9. `app/Http/Middleware/HandleInertiaRequests.php`

Thêm một dòng vào `share()`:

```php
'flash' => [
    'status' => fn () => $request->session()->get('status'),
    'error' => fn () => $request->session()->get('error'),   // ← mới
],
```

Đây là quy ước dùng chung cho toàn dự án từ đây trở đi (Q4), không phải thứ riêng của Categories.

---

## 6. Checklist test

[`phases/phase-1.md`](../../phases/phase-1.md) §8 có 3 mục chạm tới Categories; danh sách dưới đây phủ hết 3 mục đó cộng thêm phần còn lại của 5 endpoint (Q11). Dùng `RefreshDatabase`, `AssertableInertia`, `CategoryFactory` states.

### `IndexCategoryTest`

- [ ] Guest → redirect `/login`.
- [ ] Render component `Categories/Index`.
- [ ] Chỉ trả 5 field whitelist — assert scoped, **fail nếu lộ `user_id` hoặc `created_at`**.
- [ ] Mặc định **ẩn** danh mục `archived`; `active` và `inactive` đều hiện.
- [ ] `?include_archived=1` → hiện cả 3 trạng thái (Q8: tất cả, không phải chỉ archived).
- [ ] `?type=income` → chỉ trả danh mục income.
- [ ] Thứ tự: `id DESC`, không phụ thuộc type hay tên (dữ liệu test xen kẽ type và tên).
- [ ] `?type=expense` vẫn giữ thứ tự `id DESC`.
- [ ] `transactions_count` đúng, và khác 0 với danh mục `archived` đã có giao dịch.
- [ ] Không thấy danh mục của user khác.
- [ ] `?type=invalid` → 422.

### `StoreCategoryTest`

- [ ] Tạo thành công, `status` = `active`.
- [ ] Payload có `status=archived` → **bị bỏ qua**, vẫn ra `active` (Q6).
- [ ] `name` nhiều khoảng trắng giữa → bị `Str::squish` gộp lại.
- [ ] **Trùng `name` + `type`** → 422 trên field `name`.
- [ ] **Trùng tên nhưng khác `type`** → thành công.
- [ ] Trùng với một danh mục đang **`archived`** → 422. _(§8)_
- [ ] `"luong"` khi đã có `"Lương"` cùng type → 422 (collation `utf8mb4_unicode_ci`). _(§8)_
- [ ] `name` dài 101 ký tự → 422.
- [ ] Thiếu `type` / `type` không hợp lệ → 422.
- [ ] User khác có cùng `name` + `type` → **không** xung đột.
- [ ] Guest → redirect `/login`.

### `UpdateCategoryTest`

- [ ] Đổi tên thành công.
- [ ] Đổi `type` khi **chưa** có giao dịch → thành công.
- [ ] Đổi `type` khi **đã** có giao dịch → 422 trên field `type`. _(§8)_
- [ ] Đổi tên, **giữ nguyên `type`**, khi đã có giao dịch → **thành công** (guard so sánh chứ không chặn mù).
- [ ] Sửa danh mục `archived` → 422 trên field `status`.
- [ ] Sửa danh mục `inactive` → thành công.
- [ ] Submit không đổi gì → thành công (unique `ignore` chính nó).
- [ ] Đổi tên trùng một danh mục khác cùng type → 422.
- [ ] Danh mục của user khác → **404**.

### `UpdateCategoryStatusTest`

- [ ] `active` → `archived`.
- [ ] `archived` → `active` (khôi phục được, dù `isEditable()` là false).
- [ ] `archived` → `inactive`.
- [ ] Danh mục **đã có giao dịch** vẫn đổi được trạng thái.
- [ ] `status` không hợp lệ → 422.
- [ ] Danh mục của user khác → **404**.

### `DeleteCategoryTest`

- [ ] Xóa được khi chưa có giao dịch; row biến mất khỏi DB.
- [ ] **Bị chặn khi đã có giao dịch** → redirect back, `flash.error` có nội dung, row **vẫn còn** trong DB. _(§8)_
- [ ] Danh mục `archived` chưa có giao dịch → xóa được (§4.2: cột "Xóa" ✅ ở cả 3 trạng thái).
- [ ] Danh mục của user khác → **404**.
- [ ] Redirect giữ nguyên query string khi request đến từ URL có filter (Q14).

---

## 7. Trình tự triển khai

1. `app/Exceptions/CategoryInUseException.php`
2. `app/Http/Requests/Concerns/ResolvesCategory.php` + 4 FormRequest
3. `app/Services/CategoryService.php`
4. `app/Http/Controllers/CategoryController.php`
5. `routes/web.php` + `HandleInertiaRequests.php`
6. 5 file test
7. `./vendor/bin/pint` → `./vendor/bin/phpstan analyse` → `php artisan test`

Trước khi sửa bất kỳ symbol nào đã tồn tại (`HandleInertiaRequests::share`), chạy impact analysis theo `CLAUDE.md`:

```
node .gitnexus/run.cjs impact "share" --direction upstream --repo .
node .gitnexus/run.cjs detect-changes --scope all --repo .
```

---

## 8. Đã cân nhắc và cố ý bỏ

Ghi lại để không ai phải suy luận lại từ đầu.

| Bỏ qua                                             | Lý do                                                                                                                    |
| -------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| Bắt `QueryException` 1062 cho unique race          | Chỉ double-submit mới kích hoạt được, mà `LoadingOverlay` + `processing` của Inertia (commit `6b0eb86`) đã chặn. (Q10)   |
| Phân trang & tìm kiếm cho danh sách danh mục       | 10–30 bản ghi mỗi user. Thêm `q` sau này là việc 10 phút. (Q3)                                                           |
| `CategoryPolicy`                                   | Policy mặc định ném 403, trái với "404" ở plan §1. Quy tắc duy nhất là quyền sở hữu, đã nằm trong quan hệ Eloquent. (Q5) |
| Eloquent scope (`scopeVisible`, `scopeOfType`…)    | Mỗi ràng buộc chỉ có đúng một call site. Tách khi Transactions cần dùng lại. (Q12)                                       |
| `ResourceInUseException` dùng chung                | Mới có 1 trong 2 call site. Trích xuất khi làm Wallets, nếu lúc đó thực sự giống nhau. (Q13)                             |
| `GET /categories/{category}` (xem chi tiết)        | [`features/phase-1.md`](../../features/phase-1.md) §3 không có mục này.                                                  |
| Gửi `typeOptions` / `statusOptions` xuống frontend | Label hiện ra tiếng Anh, vô dụng với UI tiếng Việt; frontend dù sao cũng cần bản đồ nhãn riêng. (Q15, Q9)                |

---

## 9. Nợ kỹ thuật phát hiện trong lúc grill

**Chưa có i18n.** Dự án không có thư mục `lang/`, `APP_LOCALE = 'en'`. Trait `HasOptions::translate()` gọi `__()` nhưng vì không có file dịch nào, nó trả về chính key. Hệ quả **đang xảy ra ngay lúc này**: user tiếng Việt thấy toast `"Account updated."`, và `TransactionType::options()` cho ra `"Income"` / `"Expense"`.

Task này **không làm tệ thêm** (backend tiếp tục dùng chuỗi tiếng Anh, và không gửi enum label nào xuống frontend) nhưng cũng **không sửa**. Đây là việc cắt ngang toàn dự án — chạm vào Account, Auth, validation message, và mọi module sau — nên cần một task riêng: tạo `lang/vi/`, đặt `APP_LOCALE=vi`, bổ sung `lang/vi/validation.php`.
