import { createContext, useCallback, useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import * as authApi from './api';
import type { AuthContextValue, AuthResponse, LoginPayload, User } from './types';
import type { PageProps } from '@/types';

export const AuthContext = createContext<AuthContextValue | null>(null);

interface AuthProviderProps {
    children: React.ReactNode;
}

export function AuthProvider({ children }: AuthProviderProps) {
    const { props } = usePage<PageProps>();

    const [user, setUser] = useState<User | null>(props.auth?.user ?? null);
    const [loading, setLoading] = useState(false);

    // Sync user state on Inertia page navigations (props.auth.user changes between pages).
    useEffect(() => {
        setUser(props.auth?.user ?? null);
    }, [props.auth?.user]);

    const refreshUser = useCallback(async () => {
        setLoading(true);
        try {
            const data = await authApi.getCurrentUser();
            setUser(data?.user ?? null);
        } finally {
            setLoading(false);
        }
    }, []);

    const login = useCallback(async (payload: LoginPayload): Promise<AuthResponse> => {
        const response = await authApi.login(payload);
        setUser(response.user);
        return response;
    }, []);

    // Always clear local user state regardless of API success or failure (#7).
    const logout = useCallback(async () => {
        try {
            await authApi.logout();
        } finally {
            setUser(null);
        }
    }, []);

    const value: AuthContextValue = {
        user,
        loading,
        isAuthenticated: user !== null,
        login,
        logout,
        refreshUser,
    };

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
