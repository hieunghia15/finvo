import { z } from 'zod';

export const loginSchema = z.object({
    email: z.string().min(1, 'Email is required').pipe(z.email('Please enter a valid email address')),
    password: z.string().min(1, 'Password is required'),
    remember: z.boolean(),
});

export type LoginFormValues = z.infer<typeof loginSchema>;

export const registerSchema = z.object({
    name: z.string().min(1, 'Full name is required'),
    email: z.string().min(1, 'Email is required').pipe(z.email('Please enter a valid email address')),
    password: z.string().min(1, 'Password is required').min(6, 'Password must be at least 6 characters'),
});

export type RegisterFormValues = z.infer<typeof registerSchema>;
