import axios from 'axios';

/**
 * Axios instance for Sanctum-specific requests.
 * Uses the root URL (not /api) so the CSRF cookie endpoint is correct.
 */
const sanctumClient = axios.create({
    baseURL: '/',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

/**
 * Fetch the Sanctum CSRF cookie.
 * Must be called before state-changing requests (e.g. login).
 */
export async function getCsrfCookie(): Promise<void> {
    await sanctumClient.get('/sanctum/csrf-cookie');
}

export default sanctumClient;
