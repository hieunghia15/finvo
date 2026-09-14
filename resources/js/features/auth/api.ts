import api from '@/lib/api';
import { getCsrfCookie } from '@/lib/sanctum';
import type { AuthResponse, LoginPayload, User, UserResponse } from './types';

/**
 * Log in with email and password.
 * Fetches the CSRF cookie first, then POSTs credentials.
 */
export async function login(payload: LoginPayload): Promise<AuthResponse> {
    await getCsrfCookie();
    const response = await api.post<{ status: string; message: string; data: { user: User } }>('/login', payload);
    return {
        message: response.data.message,
        user: response.data.data.user,
    };
}

/**
 * Log out the currently authenticated user.
 */
export async function logout(): Promise<void> {
    await api.post('/logout');
}

/**
 * Fetch the currently authenticated user.
 * Returns null if unauthenticated (401).
 */
export async function getCurrentUser(): Promise<UserResponse | null> {
    try {
        const response = await api.get<{ status: string; message: string; data: { user: User } }>('/user');
        return { user: response.data.data.user };
    } catch (error: unknown) {
        if (typeof error === 'object' && error !== null && 'response' in error && (error as { response?: { status?: number } }).response?.status === 401) {
            return null;
        }
        throw error;
    }
}
