import { useContext } from 'react';
import { AuthContext } from './AuthProvider';
import type { AuthContextValue } from './types';

/**
 * Access the authentication context.
 * Must be used within an <AuthProvider>.
 */
export function useAuth(): AuthContextValue {
    const context = useContext(AuthContext);

    if (context === null) {
        throw new Error('useAuth must be used within an AuthProvider');
    }

    return context;
}
