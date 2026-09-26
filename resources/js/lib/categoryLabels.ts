import { CategoryStatus, CategoryType } from '@/types';
import { trans } from '@/lib/i18n';

/**
 * The single place the Categories screen gets its wording and badge colours
 * from. The backend sends raw enum values only, so the frontend owns the
 * labels. They are English source strings: pass them through t() to display.
 */

export const CATEGORY_TYPE_LABELS: Record<CategoryType, string> = {
    income: trans('Income'),
    expense: trans('Expense'),
};

export const CATEGORY_STATUS_LABELS: Record<CategoryStatus, string> = {
    active: trans('Active'),
    inactive: trans('Inactive'),
    archived: trans('Archived'),
};

/** What each status means for the user, from the status matrix in docs/phases/phase-1.md §4.2. */
export const CATEGORY_STATUS_DESCRIPTIONS: Record<CategoryStatus, string> = {
    active: trans('Shown in the list and can be picked for new transactions.'),
    inactive: trans('Shown in the list, but cannot be picked for new transactions.'),
    archived: trans('Hidden from the list unless "Show archived" is on. Must be restored before editing.'),
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
