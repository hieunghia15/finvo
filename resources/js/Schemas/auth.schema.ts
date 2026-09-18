import { z } from 'zod';

export const loginSchema = z.object({
    email: z.string().min(1, 'Email is required').pipe(z.email('Please enter a valid email address')),
    password: z.string().min(1, 'Password is required'),
});

export type LoginFormValues = z.infer<typeof loginSchema>;

export const registerSchema = z
    .object({
        name: z.string().trim().min(1, 'Full name is required').max(255, 'Full name must not exceed 255 characters'),
        email: z.string().trim().min(1, 'Email is required').max(255, 'Email must not exceed 255 characters').pipe(z.email('Please enter a valid email address')),
        password: z.string().min(1, 'Password is required').min(6, 'Password must be at least 6 characters'),
        password_confirmation: z.string().min(1, 'Please confirm your password'),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match',
        path: ['password_confirmation'],
    });

export type RegisterFormValues = z.infer<typeof registerSchema>;
