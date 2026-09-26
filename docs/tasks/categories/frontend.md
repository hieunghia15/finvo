# Task — Categories (Frontend)

> Nguồn: [`features/phase-1.md`](../../features/phase-1.md) §3 + [`phases/phase-1.md`](../../phases/phase-1.md) §4.2, §4.4 + [`tasks/categories/backend.md`](backend.md) + template `categories.html`, `basic-elements.html`, `modals.html` + buổi grill Q1–Q18.
> Phạm vi: **chỉ frontend**. Backend đã merge (PR #18), task này **không** sửa file PHP nào.
> Nhánh: `feat/implement-categories-fe`.

---

## 0. Tình trạng hiện tại

| Thành phần                                                                   | Trạng thái                              |
| ---------------------------------------------------------------------------- | --------------------------------------- |
| 5 route `categories.*` trong `routes/web.php`                                | ✅ có                                   |
| `CategoryController` → `Inertia::render('Categories/Index', …)`              | ✅ có — page component **chưa tồn tại** |
| Shared props `flash.status` + `flash.error` (`HandleInertiaRequests`)        | ✅ có                                   |
| `Flash` type (`status`, `error`) trong `resources/js/types/index.d.ts`       | ✅ có                                   |
| `IN_PLACE_SUBMIT`, `isInPlaceSubmit()` (`resources/js/lib/inPlaceSubmit.ts`) | ✅ có — chỉ nhận visit **khác GET**     |
| `fieldError()` (`resources/js/lib/fieldError.ts`)                            | ✅ có — dùng lại                        |
| Page, component, schema, type, menu cho Categories                           | ❌ **task này**                         |

Sự thật đã xác minh trong lúc grill:

- UI hiện tại **toàn tiếng Anh** (Account, Login, Register).
- Bootstrap JS được nạp toàn cục bằng `<script>` trong `resources/views/app.blade.php`, **không** có trong npm, không có type → không dùng `window.bootstrap`.
- `Preloader` bật cho **mọi** visit mà `isInPlaceSubmit()` trả `false` → hiện tại mỗi `router.get` đổi filter sẽ chớp màn hình "FINVO" toàn trang (`Preloader.tsx:25`).
- Z-index: `LoadingOverlay` = 9998, modal Bootstrap = 1055 → overlay che được modal khi đang submit.
- Dự án **chưa có** framework test frontend.

---

## 1. Bảng quyết định (Q1–Q18)

| #   | Quyết định                                                                                                                                                                            |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Q1  | UI **tiếng Anh**. Nhãn enum gom vào `CATEGORY_TYPE_LABELS` / `CATEGORY_STATUS_LABELS` (một chỗ duy nhất, sẵn cho i18n sau này). _→ Thay bởi [multi-lang](../multi-lang/frontend.md)._ |
| Q2  | Modal **React tự điều khiển**: `Components/Common/Modal.tsx`, markup theo `modals.html`. Không dùng `window.bootstrap`, không dùng offcanvas của template.                            |
| Q3  | **Một bảng** có cột Type (badge). Filter type là tab **All / Income / Expense** ↔ `?type=`.                                                                                           |
| Q4  | **Bỏ** ô "Search here" của template (backend không có search).                                                                                                                        |
| Q5  | Có quản lý trạng thái: cột Status (badge) + checkbox **"Show archived"** ↔ `?include_archived=1` + thao tác đổi trạng thái.                                                           |
| Q6  | Nút xóa **disabled** khi `transactions_count > 0`. Luôn có **modal xác nhận**. Vẫn hiển thị `flash.error` (backend là lớp chặn thật).                                                 |
| Q7  | ~~`FlashAlerts` (alert inline)~~ → **toast `sonner`**: `<Toaster />` trong `app.tsx` + `Components/Common/FlashToasts.tsx`. Chỉ Categories dùng trong task này.                       |
| Q8  | **Không dùng `FormField`**. Form trong modal viết markup label / input / `invalid-feedback` inline.                                                                                   |
| Q9  | Sidebar: thêm `Categories` vào nhóm **MAIN**. Giữ nguyên các mục demo.                                                                                                                |
| Q10 | Icon **"Change status"** trên mỗi dòng → `CategoryStatusModal` (select 3 trạng thái) → `PATCH /categories/{id}/status`.                                                               |
| Q11 | Type dùng `<select class="form-select form-control text-dark h-55">` **không icon**, theo mẫu "Basic Form" của `basic-elements.html`.                                                 |
| Q12 | Ma trận ràng buộc theo dòng — xem §5.                                                                                                                                                 |
| Q13 | Type mặc định khi Thêm: `filters.type ?? 'expense'`.                                                                                                                                  |
| Q14 | GET đổi filter hiện **`LoadingOverlay`**, không hiện Preloader: thêm `IN_PLACE_FILTER` + header `X-Finvo-In-Place`, mở rộng `isInPlaceSubmit()`. `replace: true`.                     |
| Q15 | `Pages/Categories/DeleteCategoryModal.tsx` riêng, dựng trên `Modal`. **Không** tạo `ConfirmModal` generic (chưa có call site thứ hai).                                                |
| Q16 | Thành công → đóng modal + flash. Lỗi → giữ modal mở, lỗi dưới field. Đang submit → chặn đóng. Mở lại → form sạch.                                                                     |
| Q17 | Type đặt ở `types/category.types.ts`, re-export từ `types/index.d.ts`.                                                                                                                |
| Q18 | Không thêm framework test. Kiểm chứng: `tsc --noEmit` + `build` + `format:check` + checklist test tay (§8).                                                                           |

---

## 2. Danh sách file

**Tạo mới**

```
resources/js/types/category.types.ts
resources/js/Schemas/category.schema.ts
resources/js/lib/categoryLabels.ts
resources/js/Components/Common/Modal.tsx
resources/js/Components/Common/FlashToasts.tsx
resources/js/Pages/Categories/Index.tsx
resources/js/Pages/Categories/CategoryTable.tsx
resources/js/Pages/Categories/CategoryFormModal.tsx
resources/js/Pages/Categories/CategoryStatusModal.tsx
resources/js/Pages/Categories/DeleteCategoryModal.tsx
```

**Sửa**

```
resources/js/types/index.d.ts          (+ re-export category.types)
resources/js/Schemas/index.ts          (+ export category.schema)
resources/js/lib/inPlaceSubmit.ts      (+ IN_PLACE_FILTER, mở rộng isInPlaceSubmit)  ⚠️ impact analysis
resources/js/Config/sidebarMenu.ts     (+ mục Categories)
resources/js/app.tsx                   (+ <Toaster /> của sonner)
package.json / package-lock.json       (+ sonner)
app/Services/CategoryService.php       (sort đổi thành id DESC — thay đổi sau khi triển khai)
tests/Feature/Category/IndexCategoryTest.php  (test thứ tự theo id DESC)
```

**Không đụng tới:** các file PHP khác, `FormField.tsx`, các trang Account/Login/Register, `Preloader.tsx`/`LoadingOverlay.tsx` (chỉ sửa comment nếu cần — logic đi qua `isInPlaceSubmit`).

---

## 3. Contract với backend (tham chiếu)

| Thao tác       | Visit                                                         | Payload                      | Kết quả                                                                 |
| -------------- | ------------------------------------------------------------- | ---------------------------- | ----------------------------------------------------------------------- |
| Xem danh sách  | `router.get('/categories', query, IN_PLACE_FILTER)`           | `type?`, `include_archived?` | props `categories`, `filters`                                           |
| Thêm           | `router.post('/categories', …, IN_PLACE_SUBMIT)`              | `name`, `type`               | `flash.status = "Category created."` / lỗi `name`, `type`               |
| Sửa            | `router.patch('/categories/{id}', …, IN_PLACE_SUBMIT)`        | `name`, `type`               | `flash.status = "Category updated."` / lỗi `name`, `type`, `status`     |
| Đổi trạng thái | `router.patch('/categories/{id}/status', …, IN_PLACE_SUBMIT)` | `status`                     | `flash.status = "Category status updated."` / lỗi `status`              |
| Xóa            | `router.delete('/categories/{id}', IN_PLACE_SUBMIT)`          | –                            | `flash.status = "Category deleted."` **hoặc** `flash.error` (đang dùng) |

- Mọi mutation trả `back()` → Referer giữ nguyên query string → filter **không** bị reset.
- Xóa bị chặn vẫn là **redirect thành công** (không phải 422) → `onSuccess` vẫn chạy. Đóng modal xóa ở `onFinish`, không dựa vào `onSuccess`.
- Lỗi backend dạng chuỗi tiếng Anh (`"You already have a category with this name and type."`, …) → hiển thị nguyên văn.
- `status` **không** gửi kèm khi Thêm/Sửa — endpoint riêng (Q2 backend).

---

## 4. Đặc tả từng file

### 4.1. `resources/js/types/category.types.ts`

```ts
/** Mirrors App\Enums\TransactionType. */
export type CategoryType = 'income' | 'expense';

/** Mirrors App\Enums\EntityStatus. */
export type CategoryStatus = 'active' | 'inactive' | 'archived';

/** One row of the `categories` prop sent by CategoryController@index. */
export interface Category {
    id: number;
    name: string;
    type: CategoryType;
    status: CategoryStatus;
    transactions_count: number;
}

/** The `filters` prop, echoed back by the backend so the controls reflect the URL. */
export interface CategoryFilters {
    type: CategoryType | null;
    include_archived: boolean;
}
```

`types/index.d.ts` thêm một dòng: `export * from './category.types';`. Import luôn đi qua `@/types`.

> Khi làm Transactions/Wallets: `'income' | 'expense'` và `'active' | 'inactive' | 'archived'` sẽ có call site thứ hai → lúc đó mới hoist thành `TransactionType` / `EntityStatus` dùng chung.

### 4.2. `resources/js/Schemas/category.schema.ts`

Phải khớp `StoreCategoryRequest` / `UpdateCategoryRequest` / `UpdateCategoryStatusRequest`.

```ts
import { z } from 'zod';

/**
 * Must match the backend StoreCategoryRequest / UpdateCategoryRequest rules.
 * Uniqueness is left to the backend: the collation ignores case and accents.
 */
export const categoryFormSchema = z.object({
    name: z.string().trim().min(1, 'Category name is required').max(100, 'Category name must not exceed 100 characters'),
    type: z.enum(['income', 'expense'], { error: 'Please choose a type' }),
});

export type CategoryFormValues = z.infer<typeof categoryFormSchema>;

/**
 * Must match the backend UpdateCategoryStatusRequest rules.
 */
export const updateCategoryStatusSchema = z.object({
    status: z.enum(['active', 'inactive', 'archived'], { error: 'Please choose a status' }),
});

export type UpdateCategoryStatusFormValues = z.infer<typeof updateCategoryStatusSchema>;
```

`Schemas/index.ts` thêm `export * from './category.schema';`.

**Không** kiểm tra trùng tên phía client: `"luong"` = `"Lương"` theo collation `utf8mb4_unicode_ci`, frontend không tái hiện được chính xác.

### 4.3. `resources/js/lib/categoryLabels.ts`

Nguồn duy nhất cho nhãn và màu badge (Q1).

```ts
export const CATEGORY_TYPE_LABELS: Record<CategoryType, string> = { income: 'Income', expense: 'Expense' };

export const CATEGORY_STATUS_LABELS: Record<CategoryStatus, string> = {
    active: 'Active',
    inactive: 'Inactive',
    archived: 'Archived',
};

/** Bootstrap colour of each badge: `badge bg-{colour} bg-opacity-10 text-{colour}`. */
export const CATEGORY_TYPE_COLORS: Record<CategoryType, string> = { income: 'success', expense: 'danger' };

export const CATEGORY_STATUS_COLORS: Record<CategoryStatus, string> = {
    active: 'primary',
    inactive: 'warning',
    archived: 'secondary',
};
```

Badge markup theo `categories.html`: `badge bg-{c} bg-opacity-10 text-{c} p-2 fs-12 fw-normal`.

Đặt thêm ở đây mô tả ngắn cho từng trạng thái (dùng trong `CategoryStatusModal`), rút từ ma trận [`phases/phase-1.md`](../../phases/phase-1.md) §4.2:

| Status   | Mô tả                                                                               |
| -------- | ----------------------------------------------------------------------------------- |
| active   | Shown in the list and can be picked for new transactions.                           |
| inactive | Shown in the list, but cannot be picked for new transactions.                       |
| archived | Hidden from the list unless "Show archived" is on. Must be restored before editing. |

### 4.4. `resources/js/lib/inPlaceSubmit.ts` ⚠️

**Trước khi sửa, chạy impact analysis** (CLAUDE.md):

```
node .gitnexus/run.cjs impact "isInPlaceSubmit" --direction upstream --repo .
```

Caller đã biết: `Preloader.tsx:26`, `LoadingOverlay.tsx:26,36`. Nếu `risk` là HIGH/CRITICAL thì báo lại trước khi sửa; `UNKNOWN` thì xác nhận bằng text search.

Thêm:

```ts
/** Marks a GET visit that only refreshes the current page, e.g. a list filter. */
export const IN_PLACE_HEADER = 'X-Finvo-In-Place';

/**
 * Visit options for a GET that re-reads the current page with other filters:
 * LoadingOverlay instead of Preloader, and no new history entry per click.
 */
export const IN_PLACE_FILTER = {
    preserveScroll: true,
    preserveState: true,
    replace: true,
    showProgress: false,
    headers: { [IN_PLACE_HEADER]: '1' },
} as const;
```

Mở rộng — **giữ nguyên tên hàm** để không phải rename ở caller:

```ts
export function isInPlaceSubmit(visit: { method: string; showProgress: boolean; headers: Record<string, string> }): boolean {
    if (visit.showProgress) {
        return false;
    }

    // Inertia's own prefetch and polling are GETs without progress too, so a
    // GET only counts when it was explicitly marked with IN_PLACE_FILTER.
    return visit.method !== 'get' || visit.headers[IN_PLACE_HEADER] === '1';
}
```

- `Visit.headers` là `Record<string, string>` trong `@inertiajs/core` → truyền `event.detail.visit` vào vẫn khớp type.
- Cập nhật block comment đầu file ("three kinds of loading indicator") để nhắc tới filter GET.
- Laravel bỏ qua header lạ — không cần sửa backend.

### 4.5. `resources/js/Components/Common/Modal.tsx`

Khung modal dùng chung (Q2). **Parent quyết định mount/unmount** — không có prop `isOpen`. Nhờ vậy mỗi lần mở là một instance mới → form và `serverErrors` tự sạch (Q16), không cần logic reset.

```ts
interface ModalProps {
    title: string;
    onClose: () => void;
    /** Disables the × button, e.g. while submitting. */
    isCloseDisabled?: boolean;
    size?: 'sm' | 'lg';
    /** Buttons rendered in `.modal-footer`. */
    footer?: ReactNode;
}
```

Yêu cầu:

- Markup theo `modals.html`: `.modal.fade.show.d-block` > `.modal-dialog.modal-dialog-centered[.modal-{size}]` > `.modal-content` > `.modal-header` (`h1.modal-title.fs-5` + `.btn-close`) / `.modal-body` / `.modal-footer`; cộng `.modal-backdrop.fade.show` riêng.
- `role="dialog"`, `aria-modal="true"`, `aria-labelledby` trỏ vào id của title (`useId()`).
- Render qua `createPortal(…, document.body)` — `main-content-container` có `overflow-hidden`.
- Khi mount: thêm class `modal-open` vào `body` (khóa scroll); khi unmount: gỡ.
- Chỉ nút × (và nút Cancel ở footer) đóng modal. **Không** đóng khi click backdrop hay nhấn Esc — _đổi sau khi triển khai, theo yêu cầu người dùng (ban đầu Esc và click backdrop cũng đóng)._ Nút × bị disable khi `isCloseDisabled`.
- Không cần animation fade-out.

Form trong modal: `<form>` bọc cả body + footer để nút submit nằm ở footer vẫn submit được form; hoặc dùng thuộc tính `form="<id>"` trên nút. Chọn một cách và dùng thống nhất cho cả 2 modal có form.

### 4.6. `resources/js/Components/Common/FlashToasts.tsx` + `<Toaster />`

(Q7, **đã đổi sau khi triển khai**) Kết quả tạo/sửa/đổi trạng thái/xóa hiện bằng **toast của `sonner`** (`npm install sonner`), **không** còn alert inline. Bản `FlashAlerts.tsx` ban đầu đã bị xóa.

- `resources/js/app.tsx`: mount **một lần** `<Toaster position="top-right" richColors closeButton />`, cạnh `Preloader` và `LoadingOverlay`.
- `FlashToasts` không nhận props, render `null`, tự đọc `usePage<PageProps>().props.flash`:
    - `flash.status` → `toast.success(...)`
    - `flash.error` → `toast.error(...)`
- **Chống lặp / chống nuốt toast:** `useEffect` phụ thuộc vào **object `flash`**, không phải nội dung chuỗi:

    ```ts
    const { flash } = usePage<PageProps>().props;

    useEffect(() => {
        if (flash?.status) toast.success(flash.status);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);
    ```

    Mỗi response của server tạo object `flash` mới → hai lần "Category updated." liên tiếp đều toast; re-render cùng trang (mở modal, gõ phím…) **không** toast lại. Đổi filter (GET) trả `flash` rỗng → không toast.

- Backend **không đổi**: vẫn `back()->with('status' | 'error', …)`.
- Lỗi validation gắn field vẫn hiện dưới field trong modal; `errors.status` vẫn là alert trong modal — chỉ thông báo "thành công / thất bại" của thao tác chuyển sang toast.
- Account/Login **giữ nguyên** alert inline — chuyển sang toast là follow-up (§10).

### 4.7. `resources/js/Pages/Categories/Index.tsx`

```ts
type IndexProps = PageProps<{ categories: Category[]; filters: CategoryFilters }>;

type ModalState = { kind: 'none' } | { kind: 'create' } | { kind: 'edit'; category: Category } | { kind: 'status'; category: Category } | { kind: 'delete'; category: Category };
```

- State modal là `useState<ModalState>` cục bộ — **không** Zustand (chỉ một trang dùng).
- Bố cục:
    1. `<Head title="Categories" />`
    2. `<Breadcrumb title="Categories" items={[{ label: 'Dashboard', url: '/dashboard' }, { label: 'Categories', active: true }]} />`
    3. `<FlashToasts />` (render `null`, chỉ phát toast)
    4. Card `bg-white border-0 rounded-3 mb-4` > `card-body p-4`:
        - Toolbar `d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 mb-lg-4`:
            - Trái: 3 nút tab **All / Income / Expense** (class giống tab Account: `btn btn-primary border border-primary py-2 px-3 fw-semibold` + `bg-primary text-white` / `bg-transparent text-primary`, `aria-current` cho tab đang chọn) + checkbox `form-check` **"Show archived"**.
            - Phải: nút **"Add New Category"** — class của template (`btn btn-outline-primary py-1 px-2 px-sm-4 fs-14 fw-medium rounded-3 hover-bg`, icon `ri-add-line`), `onClick` → `{ kind: 'create' }`.
        - `<CategoryTable … />`
    5. Modal đang mở (render có điều kiện theo `modal.kind`).
- Đổi filter:

    ```ts
    /** Only non-default filters go into the URL, so the plain list stays at /categories. */
    function toQuery(filters: CategoryFilters): Record<string, string> {
        const query: Record<string, string> = {};
        if (filters.type) query.type = filters.type;
        if (filters.include_archived) query.include_archived = '1';
        return query;
    }

    const applyFilters = (next: CategoryFilters) => router.get('/categories', toQuery(next), IN_PLACE_FILTER);
    ```

    Trạng thái của tab/checkbox **lấy từ prop `filters`**, không giữ state riêng → luôn khớp URL (reload, back/forward, bookmark).

- Type mặc định cho modal Thêm (Q13): `filters.type ?? 'expense'`.

### 4.8. `resources/js/Pages/Categories/CategoryTable.tsx`

Props: `categories`, `onEdit`, `onChangeStatus`, `onDelete` (mỗi callback nhận `Category`).

| Cột          | Nội dung                                                       |
| ------------ | -------------------------------------------------------------- |
| Name         | `category.name`                                                |
| Type         | badge theo `CATEGORY_TYPE_LABELS` / `CATEGORY_TYPE_COLORS`     |
| Transactions | `transactions_count`                                           |
| Status       | badge theo `CATEGORY_STATUS_LABELS` / `CATEGORY_STATUS_COLORS` |
| Action       | 3 nút icon: edit / change status / delete — xem ma trận §5     |

- Wrapper theo template: `default-table-area` > `table-responsive` > `table align-middle`.
- Giữ nguyên thứ tự backend gửi (`id DESC` — mới nhất trước, ở cả tab All lẫn khi lọc theo loại) — **không** sort lại. _Đã đổi từ "income trước, `name ASC`" sau khi triển khai; [`phases/phase-1.md`](../../phases/phase-1.md) §4.4, task backend, `CategoryService::listFor()` và `IndexCategoryTest` đều đã cập nhật._
- Nút action là component nội bộ `ActionButton`: ô vuông 34px (`wh-34`), nền nhạt `bg-{màu} bg-opacity-10`, `rounded-2`, icon `material-symbols-outlined fs-18 text-{màu}` — `edit` (`primary`), `toggle_on` (`warning`), `delete` (`danger`), cách nhau `gap-2`. Nút mẫu của template (icon 16px, nền trong suốt) quá nhỏ để bấm. Mỗi nút có `type="button"`, `title` và `aria-label` (ví dụ `"Edit Lương"`).
- Nút disabled: thuộc tính `disabled` + `opacity-50` + `cursor: not-allowed`; `title` giải thích lý do (§5). **Không** dùng class `.btn` — Bootstrap đặt `pointer-events: none` cho `.btn:disabled`, làm mất tooltip.
- Danh sách rỗng: một dòng `<td colSpan={5} className="text-center text-secondary">No categories found.</td>`.

### 4.9. `resources/js/Pages/Categories/CategoryFormModal.tsx`

Một component cho cả **Thêm** và **Sửa**: có prop `category` → Sửa.

```ts
interface CategoryFormModalProps {
    /** The category being edited; omitted when creating. */
    category?: Category;
    /** Pre-selected type when creating (Q13). */
    defaultType: CategoryType;
    onClose: () => void;
}
```

- Title: `"Add New Category"` / `"Edit Category"`. Nút submit: `"Create"` / `"Save Changes"`, khi đang submit: `"Creating…"` / `"Saving…"`. Nút `"Cancel"` (`btn btn-danger text-white`) đóng modal.
- TanStack Form theo đúng idiom Account: `useForm({ defaultValues, validationLogic: revalidateLogic(), validators: { onDynamic: categoryFormSchema }, onSubmit })`, `isSubmitting` qua `useStore(form.store, …)`, bọc `router.*` trong `Promise` resolve ở `onFinish`.
- `defaultValues`: `{ name: category?.name ?? '', type: category?.type ?? defaultType }`.
- Submit:
    - Thêm: `router.post('/categories', value, { ...IN_PLACE_SUBMIT, onSuccess: onClose, onError: setServerErrors, onFinish: resolve })`.
    - Sửa: `router.patch(`/categories/${category.id}`, value, { … })`.
- `serverErrors: { name?: string; type?: string; status?: string }`. Gõ vào field nào thì xóa lỗi server của field đó (như `clearServerError` ở `ChangePasswordForm`). Lỗi hiển thị bằng `fieldError(field.state.meta.errors, serverErrors.x)`.
- `serverErrors.status` (danh mục vừa bị archive ở tab khác) → `alert alert-danger` ở **đầu modal body**, vì không gắn được field nào.
- Truyền `isCloseDisabled={isSubmitting}` cho `Modal`.
- `autoFocus` ô Name.

Markup field — **inline, không dùng `FormField`** (Q8), theo mẫu **"Basic Form"** của `basic-elements.html`. **Không có icon nào bên trong input/select** (không `position-relative`, không `ps-5`, không `<i>`); chỉ giữ indicator mặc định của control (mũi tên của select, icon `is-invalid` của Bootstrap):

```tsx
<div className="form-group mb-4">
    <label htmlFor="category-name" className="label text-secondary">Name</label>
    <div className="form-group">
        <input id="category-name" className={`form-control text-dark h-55${error ? ' is-invalid' : ''}`} maxLength={100} … />
    </div>
    {error && <div className="invalid-feedback d-block">{error}</div>}
</div>
```

Ô Type: `<select className="form-select form-control text-dark h-55">` với 2 `<option>` từ `CATEGORY_TYPE_LABELS`. **Không** có option rỗng — giá trị luôn hợp lệ nhờ Q13.

**Khóa Type (Q12):** `const isTypeLocked = category !== undefined && category.transactions_count > 0;` → `disabled` trên select + hint `<span className="d-block fs-14 text-secondary mt-1">Type is locked because this category has transactions.</span>`. Giá trị vẫn nằm trong form state nên vẫn gửi lên (backend yêu cầu `type`), chỉ là không đổi được.

### 4.10. `resources/js/Pages/Categories/CategoryStatusModal.tsx`

Props: `category: Category`, `onClose`.

- Title: `"Change Status"`; body mở đầu bằng tên danh mục.
- Một `<select className="form-select form-control text-dark h-55">` (không icon, như §4.9) 3 option từ `CATEGORY_STATUS_LABELS`, mặc định = `category.status`; bên dưới là mô tả của trạng thái đang chọn (§4.3).
- TanStack Form + `updateCategoryStatusSchema`, cùng idiom §4.9.
- Nút `"Save"` **disabled khi giá trị bằng trạng thái hiện tại** hoặc đang submit.
- `router.patch(`/categories/${category.id}/status`, value, { ...IN_PLACE_SUBMIT, onSuccess: onClose, onError, onFinish })`. Lỗi `status` hiện dưới select.
- Lưu ý: archive một danh mục khi "Show archived" đang tắt → dòng đó **biến mất** sau khi lưu. Đây là hành vi đúng.

### 4.11. `resources/js/Pages/Categories/DeleteCategoryModal.tsx`

Props: `category: Category`, `onClose`.

- Title: `"Delete Category"`, `size="sm"`.
- Body: `Delete "<strong>{name}</strong>"? This cannot be undone.`
- Không dùng TanStack Form; `const [isDeleting, setIsDeleting] = useState(false)`.
- Nút `"Cancel"` + nút `"Delete"` (`btn btn-danger text-white`, `"Deleting…"` khi đang chạy).
- `router.delete(`/categories/${category.id}`, { ...IN_PLACE_SUBMIT, onFinish: onClose })` — đóng ở **`onFinish`** vì xóa bị chặn vẫn là redirect thành công (§3). `flash.error` hiện thành toast lỗi qua `FlashToasts`.
- `isCloseDisabled={isDeleting}`.

### 4.12. `resources/js/Config/sidebarMenu.ts`

Thêm vào `items` của nhóm `main` (sau các mục hiện có):

```ts
{
    id: 'categories',
    title: 'Categories',
    icon: 'category',
    url: '/categories',
},
```

Chạy trước khi sửa: `node .gitnexus/run.cjs impact "sidebarMenuConfig" --direction upstream --repo .`

---

## 5. Ma trận ràng buộc theo dòng (Q12)

Rút từ [`phases/phase-1.md`](../../phases/phase-1.md) §4.2, §4.4. Frontend chỉ **phản ánh** ràng buộc cho UX; backend vẫn là nơi chặn thật.

| Điều kiện                | Nút Edit                                                       | Ô Type trong modal Edit               | Nút Status | Nút Delete                                                               |
| ------------------------ | -------------------------------------------------------------- | ------------------------------------- | ---------- | ------------------------------------------------------------------------ |
| `status === 'archived'`  | **disabled** — title `"Restore this category before editing."` | –                                     | bật        | bật nếu `transactions_count === 0`                                       |
| `transactions_count > 0` | bật (nếu không archived)                                       | **disabled** + hint "Type is locked…" | bật        | **disabled** — title `"Categories with transactions cannot be deleted."` |
| còn lại                  | bật                                                            | bật                                   | bật        | bật                                                                      |

Hai điều kiện có thể xảy ra cùng lúc (archived **và** có giao dịch) → Edit disabled, Delete disabled, chỉ còn Status.

---

## 6. Idiom bắt buộc (đồng bộ với form Account)

- `useStore(form.store, (state) => state.isSubmitting)` — không dùng `<form.Subscribe>`.
- `onSubmit` trả `new Promise<void>` resolve ở `onFinish`.
- `...IN_PLACE_SUBMIT` cho mọi mutation → `LoadingOverlay`, giữ scroll/state.
- `fieldError(field.state.meta.errors, serverError)` — lỗi Zod ưu tiên, sau đó mới tới lỗi server.
- `<form onSubmit={(e) => { e.preventDefault(); e.stopPropagation(); form.handleSubmit(); }}>`.
- Comment bằng tiếng Anh, giải thích **vì sao**, mật độ như `ProfileForm.tsx`.

---

## 7. Trình tự triển khai

1. `types/category.types.ts` + re-export trong `types/index.d.ts`
2. `Schemas/category.schema.ts` + export trong `Schemas/index.ts`
3. `lib/categoryLabels.ts`
4. Impact analysis → `lib/inPlaceSubmit.ts` (`IN_PLACE_FILTER`, `isInPlaceSubmit`)
5. `npm install sonner` → `<Toaster />` trong `app.tsx` → `Components/Common/Modal.tsx`, `Components/Common/FlashToasts.tsx`
6. `Pages/Categories/CategoryTable.tsx` → `Index.tsx` (chưa có modal) — kiểm tra list + filter
7. `CategoryFormModal.tsx` → `CategoryStatusModal.tsx` → `DeleteCategoryModal.tsx`
8. Impact analysis → `Config/sidebarMenu.ts`
9. `npx tsc --noEmit` → `npm run format` → `npm run format:check` → `npm run build`
10. Chạy checklist §8 trên trình duyệt
11. Trước khi commit: `node .gitnexus/run.cjs detect-changes --scope all --repo .` (không chấp nhận kết quả `partial`/`truncated`)

---

## 8. Checklist test tay

Chuẩn bị: một user mới đăng ký (có sẵn 11 danh mục seed). Tạo vài giao dịch qua tinker/factory cho một danh mục (ví dụ "Ăn uống") để có `transactions_count > 0`.

### Danh sách & filter

- [ ] Sidebar có mục **Categories**, bấm vào tới `/categories`.
- [ ] Mặc định: 11 danh mục, sắp theo **id giảm dần** (mới nhất trên cùng); URL là `/categories` (không có query).
- [ ] Tab **Income** → chỉ income, vẫn **id giảm dần**, URL `?type=income`; tab **All** → URL sạch.
- [ ] Đổi tab / checkbox: hiện **`LoadingOverlay`**, **không** chớp Preloader "FINVO"; scroll giữ nguyên.
- [ ] Nhấn Back sau vài lần đổi tab → rời trang Categories (do `replace: true`), không lùi từng tab.
- [ ] Reload tại `/categories?type=expense&include_archived=1` → tab Expense + checkbox đang bật.
- [ ] Danh mục archived **ẩn** mặc định; bật "Show archived" → hiện cả active, inactive, archived.
- [ ] Filter ra danh sách rỗng → dòng "No categories found."
- [ ] Điều hướng sang trang khác (ví dụ Account) → **vẫn** hiện Preloader như cũ (không hồi quy).
- [ ] Submit form Account → vẫn hiện `LoadingOverlay` (không hồi quy).

### Thêm

- [ ] Ở tab All, mở "Add New Category" → Type mặc định **Expense**; ở tab Income → mặc định **Income**.
- [ ] Để trống Name → lỗi Zod, không gửi request.
- [ ] Name 101 ký tự → không gõ được quá 100 (`maxLength`) / lỗi Zod.
- [ ] Tạo "Cafe" (expense) → modal đóng, **toast success** "Category created.", dòng mới nằm **trên cùng**, filter giữ nguyên.
- [ ] Tạo "an uong" (expense) khi đã có "Ăn uống" → modal **giữ mở**, lỗi dưới Name: "You already have a category with this name and type."
- [ ] Gõ lại vào Name → lỗi server biến mất.
- [ ] Tạo "Ăn uống" nhưng type **Income** → thành công.
- [ ] Đóng rồi mở lại modal → form trống, không còn lỗi cũ.
- [ ] Click ra ngoài modal hoặc nhấn Esc → modal **không** đóng; chỉ nút × / Cancel đóng. Trong lúc đang submit: nút × bị disable.

### Sửa

- [ ] Sửa tên danh mục chưa có giao dịch, đổi cả Type → thành công, **toast success** "Category updated.".
- [ ] Danh mục **có giao dịch**: ô Type bị disabled + hint; đổi tên vẫn thành công.
- [ ] Danh mục **archived**: nút Edit disabled, hover thấy title giải thích.
- [ ] Mở Edit rồi submit không đổi gì → thành công.

### Đổi trạng thái

- [ ] Active → Inactive: badge đổi màu, dòng vẫn hiện.
- [ ] Active → Archived khi "Show archived" tắt → dòng biến mất; bật lại checkbox → thấy dòng với badge Archived.
- [ ] Archived → Active (khôi phục) → nút Edit bật lại.
- [ ] Nút Save disabled khi chưa đổi lựa chọn.
- [ ] Danh mục có giao dịch vẫn đổi trạng thái được.

### Xóa

- [ ] Danh mục chưa có giao dịch → modal xác nhận → Delete → dòng biến mất, **toast success** "Category deleted.".
- [ ] Danh mục có giao dịch → nút Delete **disabled**.
- [ ] Mô phỏng race (mở modal xóa, rồi tạo giao dịch cho danh mục đó qua tinker, rồi bấm Delete) → modal đóng, **toast error** với nội dung `flash.error`, dòng vẫn còn.
- [ ] Danh mục archived chưa có giao dịch → xóa được.

### Toast

- [ ] Hai mutation cùng loại liên tiếp (ví dụ sửa hai lần) → **hai** toast; toast có nút × để đóng. Trang **không** còn alert flash inline.
- [ ] Mở/đóng modal, gõ phím, đổi tab filter sau một mutation → **không** toast lại lần nữa.

### Chung

- [ ] Ở độ rộng mobile: toolbar wrap gọn, bảng cuộn ngang trong `table-responsive`, modal vừa màn hình.
- [ ] Tab bàn phím: đi được qua tab filter, checkbox, nút action, các field trong modal.

---

## 9. Đã cân nhắc và cố ý bỏ

| Bỏ qua                                        | Lý do                                                                                         |
| --------------------------------------------- | --------------------------------------------------------------------------------------------- |
| Offcanvas của `categories.html` cho form Thêm | Người dùng chỉ định `modals.html`; một cơ chế (modal) cho mọi thao tác. (Q2)                  |
| `window.bootstrap.Modal`                      | Không có type, DOM do Bootstrap điều khiển dễ xung đột với React re-render. (Q2)              |
| Ô search                                      | Backend không có `q`; lọc phía client sẽ lệch khỏi các filter khác (không nằm trên URL). (Q4) |
| `<select>` status inline gửi ngay             | Dễ archive nhầm. (Q10)                                                                        |
| Status trong modal Edit                       | Phải gọi 2 endpoint; modal Edit bị chặn với archived nên không khôi phục được. (Q10)          |
| `ConfirmModal` generic                        | Mới có 1 call site. Trích xuất khi làm Wallets nếu giống nhau. (Q15)                          |
| Tái dùng `FormField`                          | Giữ `FormField` trong phạm vi trang Account. (Q8)                                             |
| Kiểm tra trùng tên phía client                | Collation không phân biệt hoa/thường và dấu; frontend không tái hiện chính xác được.          |
| Nút "Save & add another"                      | User mới đã có 11 danh mục seed; hiếm khi thêm hàng loạt. (Q16)                               |
| Framework test frontend (Vitest…)             | Việc cắt ngang toàn dự án, cần task riêng. (Q18)                                              |
| Zustand cho state modal                       | Chỉ một trang dùng → `useState` cục bộ, đúng `frontend.md`.                                   |

---

## 10. Follow-up (ngoài phạm vi task này)

- Chuyển alert inline ở `Pages/Account/Index.tsx` và `Pages/Login/Index.tsx` sang toast `sonner` qua `FlashToasts`. (Q7)
- ~~i18n toàn dự án~~ → [`tasks/multi-lang/frontend.md`](../multi-lang/frontend.md). (Q1)
- Hạ tầng test frontend. (Q18)
- Hoist `CategoryType` / `CategoryStatus` thành `TransactionType` / `EntityStatus` dùng chung khi Transactions/Wallets cần. (§4.1)
- Sidebar chưa highlight mục theo URL hiện tại — áp dụng cho mọi mục, không riêng Categories.
