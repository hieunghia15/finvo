import React, { createContext, useCallback, useEffect, useState } from 'react';
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
    const initialUser = (props.auth?.user as User | null) ?? null;

    const [user, setUser] = useState<User | null>(initialUser);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        setUser((props.auth?.user as User | null) ?? null);
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

    const logout = useCallback(async () => {
        await authApi.logout();
        setUser(null);
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
