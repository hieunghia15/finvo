import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { isInPlaceSubmit } from '@/lib/inPlaceSubmit';

/**
 * Keeps the spinner up long enough to be seen when the server answers fast.
 */
const MIN_VISIBLE_MS = 300;

/**
 * Blurred overlay with a spinner, shown while a form sent with
 * IN_PLACE_SUBMIT is being submitted.
 */
export default function LoadingOverlay() {
    const [isVisible, setIsVisible] = useState(false);
    const shownAtRef = useRef(0);
    const hideTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        const clearHideTimer = () => {
            if (hideTimerRef.current) {
                clearTimeout(hideTimerRef.current);
                hideTimerRef.current = null;
            }
        };

        const unbindStart = router.on('start', (event) => {
            if (isInPlaceSubmit(event.detail.visit)) {
                clearHideTimer();
                shownAtRef.current = Date.now();
                setIsVisible(true);
            }
        });
        const unbindFinish = router.on('finish', (event) => {
            if (isInPlaceSubmit(event.detail.visit)) {
                const remaining = MIN_VISIBLE_MS - (Date.now() - shownAtRef.current);
                clearHideTimer();
                hideTimerRef.current = setTimeout(
                    () => {
                        hideTimerRef.current = null;
                        setIsVisible(false);
                    },
                    Math.max(remaining, 0)
                );
            }
        });

        return () => {
            clearHideTimer();
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
