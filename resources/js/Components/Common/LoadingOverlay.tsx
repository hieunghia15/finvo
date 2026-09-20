import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { isInPlaceSubmit } from '@/lib/inPlaceSubmit';

/** Keep the spinner up this long so it does not just flash when the server answers fast. */
const MINIMUM_VISIBLE_MS = 300;

/**
 * Blurred overlay with a spinner, shown while a form submitted with
 * IN_PLACE_SUBMIT is being saved. Page navigations show Preloader instead.
 */
export default function LoadingOverlay() {
    const [isVisible, setIsVisible] = useState(false);
    const shownAt = useRef(0);
    const hideTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        const cancelPendingHide = () => {
            if (hideTimer.current) {
                clearTimeout(hideTimer.current);
                hideTimer.current = null;
            }
        };

        const unbindStart = router.on('start', (event) => {
            if (!isInPlaceSubmit(event.detail.visit)) {
                return;
            }

            cancelPendingHide();
            shownAt.current = Date.now();
            setIsVisible(true);
        });

        const unbindFinish = router.on('finish', (event) => {
            if (!isInPlaceSubmit(event.detail.visit)) {
                return;
            }

            // The new data is already rendered underneath; only the overlay waits.
            const remainingMs = Math.max(MINIMUM_VISIBLE_MS - (Date.now() - shownAt.current), 0);

            cancelPendingHide();
            hideTimer.current = setTimeout(() => {
                hideTimer.current = null;
                setIsVisible(false);
            }, remainingMs);
        });

        return () => {
            cancelPendingHide();
            unbindStart();
            unbindFinish();
        };
    }, []);

    if (!isVisible) {
        return null;
    }

    return (
        <div
            className="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
            // Just below the Preloader (z-index 9999 in the Trezo theme).
            style={{ zIndex: 9998, backgroundColor: 'rgba(255, 255, 255, 0.6)', backdropFilter: 'blur(3px)' }}
            aria-busy="true"
        >
            <div className="spinner-border text-primary" role="status">
                <span className="visually-hidden">Loading...</span>
            </div>
        </div>
    );
}
