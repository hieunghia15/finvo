import { createInertiaApp, ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

import LoadingOverlay from '@/Components/Common/LoadingOverlay';
import Preloader from '@/Components/Common/Preloader';

const appName = 'Finvo';

interface PageModule {
    default: ResolvedComponent;
}

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: async (name) => {
        const page = await resolvePageComponent<PageModule>(`./Pages/${name}.tsx`, import.meta.glob<PageModule>('./Pages/**/*.tsx'));
        return page.default;
    },
    setup({ el, App, props }) {
        const container = el || document.getElementById('app');
        if (container) {
            createRoot(container).render(
                <>
                    <Preloader />
                    <LoadingOverlay />
                    <App {...props}>{({ Component, key, props: pageProps }) => <Component key={key} {...pageProps} />}</App>
                </>
            );
        }
    },
    progress: {
        color: '#3368FC',
    },
});
