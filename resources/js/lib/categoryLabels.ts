import { CategoryStatus, CategoryType } from '@/types';

/**
 * The single place the Categories screen gets its wording and badge colours
 * from. The backend sends raw enum values only, so the frontend owns the
 * labels; keeping them here leaves one file to touch when i18n arrives.
 */

export const CATEGORY_TYPE_LABELS: Record<CategoryType, string> = {
    income: 'Income',
    expense: 'Expense',
};

export const CATEGORY_STATUS_LABELS: Record<CategoryStatus, string> = {
    active: 'Active',
    inactive: 'Inactive',
    archived: 'Archived',
};

/** What each status means for the user, from the status matrix in plan_phase_1.md §4.2. */
export const CATEGORY_STATUS_DESCRIPTIONS: Record<CategoryStatus, string> = {
    active: 'Shown in the list and can be picked for new transactions.',
    inactive: 'Shown in the list, but cannot be picked for new transactions.',
    archived: 'Hidden from the list unless "Show archived" is on. Must be restored before editing.',
};

/** Bootstrap colour of each badge: `badge bg-{colour} bg-opacity-10 text-{colour}`. */
export const CATEGORY_TYPE_COLORS: Record<CategoryType, string> = {
    income: 'success',
    expense: 'danger',
};

export const CATEGORY_STATUS_COLORS: Record<CategoryStatus, string> = {
    active: 'primary',
    inactive: 'warning',
    archived: 'secondary',
};

/** Badge classes from the Trezo categories template, in the given colour. */
export function badgeClass(color: string): string {
    return `badge bg-${color} bg-opacity-10 text-${color} p-2 fs-12 fw-normal`;
}
