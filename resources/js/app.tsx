import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { AuthProvider } from '@/features/auth/AuthProvider';

import Preloader from '@/Components/Common/Preloader';

const appName = 'Finvo';

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')) as any,
    setup({ el, App, props }) {
        const container = el || document.getElementById('app');
        if (container) {
            createRoot(container).render(
                <>
                    <Preloader />
                    <App {...props}>
                        {({ Component, key, props: pageProps }) => (
                            <AuthProvider>
                                <Component key={key} {...pageProps} />
                            </AuthProvider>
                        )}
                    </App>
                </>
            );
        }
    },
    progress: {
        color: '#3368FC',
    },
});
