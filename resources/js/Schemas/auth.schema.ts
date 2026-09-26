import { z } from 'zod';
import { trans } from '@/lib/i18n';

/**
 * Must match the backend ValidEmail rule.
 */
const emailRegex = /^([a-zA-Z0-9_.+-])+@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,64})+$/;

/**
 * Must match the backend ValidPassword rule.
 */
export const passwordRegex = /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,32}$/;

export const loginSchema = z.object({
    email: z.string().trim().min(1, trans('Email is required')).regex(emailRegex, trans('Please enter a valid email address')),

    password: z.string().min(1, trans('Password is required')),

    remember: z.boolean(),
});

export type LoginFormValues = z.infer<typeof loginSchema>;

export const registerSchema = z
    .object({
        name: z.string().trim().min(1, trans('Full name is required')).max(255, trans('Full name must not exceed 255 characters')),

        email: z.string().trim().min(1, trans('Email is required')).max(255, trans('Email must not exceed 255 characters')).regex(emailRegex, trans('Please enter a valid email address')),

        password: z
            .string()
            .min(1, trans('Password is required'))
            .regex(passwordRegex, trans('Password must be 6–32 characters and contain at least one uppercase letter, one lowercase letter, one number, and one special character.')),

        password_confirmation: z.string().min(1, trans('Please confirm your password')),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: trans('Passwords do not match'),
        path: ['password_confirmation'],
    });

export type RegisterFormValues = z.infer<typeof registerSchema>;
