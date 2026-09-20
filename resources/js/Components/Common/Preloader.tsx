import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { isInPlaceSubmit } from '@/lib/inPlaceSubmit';

const LETTERS = ['F', 'I', 'N', 'V', 'O'];

/**
 * Full-page "FINVO" loading screen, shown on the first page load and while
 * Inertia navigates to another page. Forms submitted with IN_PLACE_SUBMIT show
 * LoadingOverlay instead, so the page stays visible while it is being saved.
 */
export default function Preloader() {
    const [isLoading, setIsLoading] = useState(() => document.readyState !== 'complete');

    useEffect(() => {
        const hide = () => setIsLoading(false);

        // The browser may have finished loading between the first render and
        // here, in which case the "load" event has already fired.
        if (document.readyState === 'complete') {
            hide();
        }
        window.addEventListener('load', hide);

        const unbindStart = router.on('start', (event) => {
            if (!isInPlaceSubmit(event.detail.visit)) {
                setIsLoading(true);
            }
        });
        const unbindFinish = router.on('finish', hide);

        return () => {
            window.removeEventListener('load', hide);
            unbindStart();
            unbindFinish();
        };
    }, []);

    if (!isLoading) {
        return null;
    }

    return (
        // Both wrappers are needed: the theme styles the letters through the
        // selector "#preloader .preloader .waviy".
        <div className="preloader" id="preloader">
            <div className="preloader">
                <div className="waviy position-relative">
                    {LETTERS.map((letter) => (
                        <span key={letter} className="d-inline-block">
                            {letter}
                        </span>
                    ))}
                </div>
            </div>
        </div>
    );
}
