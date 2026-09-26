import { createInertiaApp, ResolvedComponent, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';

import LoadingOverlay from '@/Components/Common/LoadingOverlay';
import Preloader from '@/Components/Common/Preloader';
import { PageProps } from '@/types';

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
            // Blade only renders <html lang> on the first load; keep it in step
            // with the language after every Inertia visit, e.g. a switch.
            router.on('success', (event) => {
                document.documentElement.lang = (event.detail.page.props as unknown as PageProps).locale;
            });

            createRoot(container).render(
                <>
                    <Preloader />
                    <Toaster position="top-right" richColors closeButton />
                    <App {...props}>
                        {({ Component, key, props: pageProps }) => (
                            <>
                                <Component key={key} {...pageProps} />
                                {/* Inside App so it can read the translations; unkeyed, so it survives navigations. */}
                                <LoadingOverlay />
                            </>
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
