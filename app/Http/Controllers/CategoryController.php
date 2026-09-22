<?php

namespace App\Http\Controllers;

use App\Exceptions\CategoryInUseException;
use App\Http\Requests\Category\IndexCategoryRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryStatusRequest;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  CategoryService  $categoryService  Reads and writes the user's categories.
     */
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Display the current user's categories.
     *
     * Every mutation below redirects back to this screen, which keeps the
     * filters in the query string intact.
     *
     * @param  IndexCategoryRequest  $request  The validated list filters.
     */
    public function index(IndexCategoryRequest $request): Response
    {
        $filters = $this->categoryService->normalizeFilters($request->validated());
        $categories = $this->categoryService->listFor($request->user(), $filters);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Create a category for the current user.
     *
     * @param  StoreCategoryRequest  $request  The validated category form submission.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->create($request->user(), $request->validated());

        return back()->with('status', 'Category created.');
    }

    /**
     * Update a category's name and type.
     *
     * @param  UpdateCategoryRequest  $request  The validated category form submission.
     */
    public function update(UpdateCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->update($request->category(), $request->validated());

        return back()->with('status', 'Category updated.');
    }

    /**
     * Move a category to another status.
     *
     * @param  UpdateCategoryStatusRequest  $request  The validated status change.
     */
    public function updateStatus(UpdateCategoryStatusRequest $request): RedirectResponse
    {
        $this->categoryService->updateStatus($request->category(), $request->status());

        return back()->with('status', 'Category status updated.');
    }

    /**
     * Delete a category that has no transactions.
     *
     * The only action without a form request, so it resolves the category
     * itself. A category still in use is not a validation failure of any
     * field, so it comes back as a flashed error rather than an error bag.
     *
     * @param  Request  $request  The current request, used for the authenticated user.
     * @param  string  $category  The category id from the route.
     */
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
}
