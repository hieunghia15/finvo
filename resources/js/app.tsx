import { createInertiaApp, ResolvedComponent, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'sonner';

import LoadingOverlay from '@/Components/Common/LoadingOverlay';
import Preloader from '@/Components/Common/Preloader';
import { Locale, PageProps } from '@/types';

const appName = 'Finvo';

interface PageModule {
    default: ResolvedComponent;
}

function pageLocale(page: { props: unknown }): Locale {
    return (page.props as PageProps).locale;
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
            // with every server response, e.g. a language switch.
            let serverLocale = pageLocale(props.initialPage);

            router.on('success', (event) => {
                serverLocale = pageLocale(event.detail.page);
                document.documentElement.lang = serverLocale;
            });

            // Back/Forward restores a page with the props it was saved with, so
            // one saved before a language switch would show the old language.
            // Reload it: the server answers in the language of the cookie.
            router.on('navigate', (event) => {
                if (pageLocale(event.detail.page) !== serverLocale) {
                    router.reload();
                }
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
