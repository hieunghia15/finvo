import axios from 'axios';

/**
 * Axios instance configured for API requests.
 * Uses cookie-based authentication (Laravel Sanctum SPA mode).
 */
const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export default api;
