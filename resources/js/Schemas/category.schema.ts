import { z } from 'zod';
import { trans } from '@/lib/i18n';

/**
 * Must match the backend StoreCategoryRequest / UpdateCategoryRequest rules.
 * Uniqueness is left to the backend: the collation ignores case and accents,
 * so "luong" and "Lương" clash there in a way the browser cannot reproduce.
 */
export const categoryFormSchema = z.object({
    name: z.string().trim().min(1, trans('Category name is required')).max(100, trans('Category name must not exceed 100 characters')),

    type: z.enum(['income', 'expense'], { error: trans('Please choose a type') }),
});

export type CategoryFormValues = z.infer<typeof categoryFormSchema>;

/**
 * Must match the backend UpdateCategoryStatusRequest rules.
 */
export const updateCategoryStatusSchema = z.object({
    status: z.enum(['active', 'inactive', 'archived'], { error: trans('Please choose a status') }),
});

export type UpdateCategoryStatusFormValues = z.infer<typeof updateCategoryStatusSchema>;
