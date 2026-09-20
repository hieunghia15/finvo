<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Enums\TransactionType;
use App\Exceptions\CategoryInUseException;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * List a user's categories for the index screen.
     *
     * @param  User  $user  The owner whose categories are listed.
     * @param  TransactionType|null  $type  Narrow to one type, or null for both.
     * @param  bool  $includeArchived  When true the list covers every status, not only the archived ones.
     *
     * @return Collection<int, Category> Categories with their transaction counts, ordered for display.
     */
    public function listFor(User $user, ?TransactionType $type, bool $includeArchived): Collection
    {
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
            ->get();
    }

    /**
     * Create a category for a user.
     *
     * Goes through the relationship so user_id comes from the session and
     * never from request input. The status is left to the model default.
     *
     * @param  User  $user  The owner of the new category.
     * @param  string  $name  The validated, normalized name.
     * @param  TransactionType  $type  Whether it categorizes income or expense.
     *
     * @return Category The created category.
     */
    public function create(User $user, string $name, TransactionType $type): Category
    {
        return $user->categories()->create([
            'name' => $name,
            'type' => $type,
        ]);
    }

    /**
     * Update a category's details.
     *
     * The caller must have already rejected an edit to an archived category
     * and a type change on a category that has transactions.
     *
     * @param  Category  $category  The category to update.
     * @param  string  $name  The validated, normalized name.
     * @param  TransactionType  $type  The validated type.
     *
     * @return Category The updated category.
     */
    public function update(Category $category, string $name, TransactionType $type): Category
    {
        $category->update([
            'name' => $name,
            'type' => $type,
        ]);

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
