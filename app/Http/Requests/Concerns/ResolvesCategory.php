<?php

namespace App\Http\Requests\Concerns;

use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Resolves the {category} route parameter for requests that act on one.
 *
 * The lookup goes through the current user's relationship, so another user's
 * row raises a ModelNotFoundException and surfaces as a 404 rather than a 403.
 *
 * @phpstan-require-extends FormRequest
 */
trait ResolvesCategory
{
    protected ?Category $resolvedCategory = null;

    /**
     * The category this request acts on, owned by the current user.
     *
     * Memoized because both rules() and the controller read it within a
     * single request.
     *
     *
     * @throws ModelNotFoundException<Category> When it is missing or owned by someone else.
     *
     * @return Category The resolved category.
     */
    public function category(): Category
    {
        return $this->resolvedCategory ??= $this->user()
            ->categories()
            ->findOrFail($this->route('category'));
    }
}
