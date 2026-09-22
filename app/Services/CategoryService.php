<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Enums\TransactionType;
use App\Exceptions\CategoryInUseException;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Collection;

class CategoryService
{
    /**
     * Read the validated list filters into the shape the index screen uses.
     *
     * The result is also sent back to the frontend so the filter controls
     * reflect the current query. Normalizing an already normalized array
     * returns it unchanged.
     *
     * When include_archived is on, the list shows every status rather than
     * only the archived ones: the question it answers is "where did my
     * category go?".
     *
     * @param  array{type?: string|null, include_archived?: bool|int|string|null}  $filters  The validated list filters.
     *
     * @return array{type: string|null, include_archived: bool} The type to narrow to, or null for both, and whether archived categories join the list.
     */
    public function normalizeFilters(array $filters): array
    {
        return [
            'type' => $filters['type'] ?? null,
            'include_archived' => (bool) ($filters['include_archived'] ?? false),
        ];
    }

    /**
     * List a user's categories for the index screen.
     *
     * Only whitelisted fields are returned, so user_id and the timestamps
     * never reach the frontend.
     *
     * @param  User  $user  The owner whose categories are listed.
     * @param  array{type?: string|null, include_archived?: bool|int|string|null}  $filters  The validated list filters; normalized here.
     *
     * @return Collection<int, array<string, mixed>> Categories with their transaction counts, ordered for display.
     */
    public function listFor(User $user, array $filters): Collection
    {
        ['type' => $type, 'include_archived' => $includeArchived] = $this->normalizeFilters($filters);

        return $user->categories()
            ->withCount('transactions')
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when(!$includeArchived, fn ($query) => $query->whereNot('status', EntityStatus::Archived))
            // Income comes first. Ordering by the column itself would not do:
            // 'expense' sorts before 'income' alphabetically.
            ->orderByRaw('FIELD(type, ?, ?)', [
                TransactionType::Income->value,
                TransactionType::Expense->value,
            ])
            ->orderBy('name')
            ->get()
            ->map->only([
                'id',
                'name',
                'type',
                'status',
                'transactions_count',
            ]);
    }

    /**
     * Create a category for a user.
     *
     * Goes through the relationship so user_id comes from the session and
     * never from request input. The status is left to the model default, so
     * $data must not carry one.
     *
     * @param  User  $user  The owner of the new category.
     * @param  array{name: string, type: string}  $data  Validated attributes; the model cast turns the type into a TransactionType.
     *
     * @return Category The created category.
     */
    public function create(User $user, array $data): Category
    {
        return $user->categories()->create($data);
    }

    /**
     * Update a category's details.
     *
     * The caller must have already rejected an edit to an archived category
     * and a type change on a category that has transactions. The status is
     * changed through updateStatus(), so $data must not carry one.
     *
     * @param  Category  $category  The category to update.
     * @param  array{name: string, type: string}  $data  Validated attributes; the model cast turns the type into a TransactionType.
     *
     * @return Category The updated category.
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }

    /**
     * Move a category to another status.
     *
     * Every transition is allowed, including restoring an archived category.
     *
     * @param  Category  $category  The category to move.
     * @param  EntityStatus  $status  The status to move it to.
     *
     * @return Category The updated category.
     */
    public function updateStatus(Category $category, EntityStatus $status): Category
    {
        $category->update(['status' => $status]);

        return $category;
    }

    /**
     * Delete a category that is not in use.
     *
     * The transactions.category_id foreign key is RESTRICT, but that is the
     * last line of defence rather than the first: it would surface as a
     * database error instead of a message the user can act on.
     *
     * @param  Category  $category  The category to delete.
     *
     * @throws CategoryInUseException When it still has transactions.
     */
    public function delete(Category $category): void
    {
        if ($category->transactions()->exists()) {
            throw new CategoryInUseException($category);
        }

        $category->delete();
    }
}
