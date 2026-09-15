import { User } from '@/types';

export type { User } from '@/types';

export interface LoginPayload {
    email: string;
    password: string;
}

export interface AuthResponse {
    message: string;
    user: User;
}

export interface UserResponse {
    user: User;
}

export interface AuthState {
    user: User | null;
    loading: boolean;
    isAuthenticated: boolean;
}

export interface AuthContextValue extends AuthState {
    login: (payload: LoginPayload) => Promise<AuthResponse>;
    logout: () => Promise<void>;
    refreshUser: () => Promise<void>;
}
