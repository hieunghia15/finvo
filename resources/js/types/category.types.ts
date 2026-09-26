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
