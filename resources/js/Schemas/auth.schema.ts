import { z } from 'zod';

/**
 * Must match the backend ValidEmail rule.
 */
const emailRegex = /^([a-zA-Z0-9_.+-])+@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,64})+$/;

/**
 * Must match the backend ValidPassword rule.
 */
export const passwordRegex = /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,32}$/;

export const loginSchema = z.object({
    email: z.string().trim().min(1, 'Email is required').regex(emailRegex, 'Please enter a valid email address'),

    password: z.string().min(1, 'Password is required'),

    remember: z.boolean(),
});

export type LoginFormValues = z.infer<typeof loginSchema>;

export const registerSchema = z
    .object({
        name: z.string().trim().min(1, 'Full name is required').max(255, 'Full name must not exceed 255 characters'),

        email: z.string().trim().min(1, 'Email is required').max(255, 'Email must not exceed 255 characters').regex(emailRegex, 'Please enter a valid email address'),

        password: z
            .string()
            .min(1, 'Password is required')
            .regex(passwordRegex, 'Password must be 6–32 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.'),

        password_confirmation: z.string().min(1, 'Please confirm your password'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match',
        path: ['password_confirmation'],
    });

export type RegisterFormValues = z.infer<typeof registerSchema>;
