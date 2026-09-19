import { z } from 'zod';
import { passwordRegex } from './auth.schema';

/**
 * Must match the backend UpdateAccountRequest rules.
 */
export const updateAccountSchema = z.object({
    name: z.string().trim().min(1, 'Full name is required').max(255, 'Full name must not exceed 255 characters'),
});

export type UpdateAccountFormValues = z.infer<typeof updateAccountSchema>;

/**
 * Must match the backend UpdatePasswordRequest rules.
 */
export const updatePasswordSchema = z
    .object({
        current_password: z.string().min(1, 'Current password is required'),

        password: z
            .string()
            .min(1, 'New password is required')
            .regex(passwordRegex, 'Password must be 6–32 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.'),

        password_confirmation: z.string().min(1, 'Please confirm your new password'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match',
        path: ['password_confirmation'],
    })
    .refine((data) => data.password !== data.current_password, {
        message: 'The new password must be different from the current password',
        path: ['password'],
    });

export type UpdatePasswordFormValues = z.infer<typeof updatePasswordSchema>;
