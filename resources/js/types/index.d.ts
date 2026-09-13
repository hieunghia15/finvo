export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    avatar?: string;
    role?: string;
}

export interface Auth {
    user: User | null;
}

export interface Flash {
    success?: string;
    error?: string;
    info?: string;
    warning?: string;
}

export type PageProps<T = Record<string, unknown>> = T & {
    auth: Auth;
    flash?: Flash;
    errors: Record<string, string>;
};

export interface BreadcrumbItem {
    label: string;
    url?: string;
    active?: boolean;
}

export interface NotificationItem {
    id: string | number;
    title: string;
    time: string;
    type?: 'sms' | 'person' | 'email' | string;
    read?: boolean;
    url?: string;
}
