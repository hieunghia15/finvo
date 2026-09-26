import { z } from 'zod';

/**
 * Must match the backend StoreCategoryRequest / UpdateCategoryRequest rules.
 * Uniqueness is left to the backend: the collation ignores case and accents,
 * so "luong" and "Lương" clash there in a way the browser cannot reproduce.
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
