import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';

export interface PreloaderProps {
    /**
     * Array of letter characters to animate in the waviy preloader.
     * Defaults to ['F', 'I', 'N', 'V', 'O'].
     */
    letters?: string[];
    /**
     * Minimum delay in milliseconds before hiding the preloader.
     * Useful to prevent visual flash on rapid responses.
     */
    minDelay?: number;
}

export const Preloader: React.FC<PreloaderProps> = ({ letters = ['F', 'I', 'N', 'V', 'O'], minDelay = 0 }) => {
    const [isLoading, setIsLoading] = useState<boolean>(() => {
        return typeof window !== 'undefined' && document.readyState !== 'complete';
    });

    // useRef keeps a stable timer reference across renders without triggering re-renders.
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        const hidePreloader = () => {
            if (timerRef.current) clearTimeout(timerRef.current);

            if (minDelay > 0) {
                timerRef.current = setTimeout(() => {
                    setIsLoading(false);
                    timerRef.current = null;
                }, minDelay);
            } else {
                setIsLoading(false);
            }
        };

        const showPreloader = () => {
            // Cancel any pending hide timer so a new navigation can show the preloader immediately.
            if (timerRef.current) {
                clearTimeout(timerRef.current);
                timerRef.current = null;
            }
            setIsLoading(true);
        };

        // Handle hard initial page load / refresh.
        // Store the same function reference so removeEventListener can find it.
        let handleLoad: (() => void) | null = null;

        if (document.readyState === 'complete') {
            setIsLoading(false);
        } else {
            handleLoad = () => hidePreloader();
            window.addEventListener('load', handleLoad);
        }

        // Listen to Inertia router SPA navigation events.
        const unbindStart = router.on('start', showPreloader);
        const unbindFinish = router.on('finish', hidePreloader);

        return () => {
            if (timerRef.current) clearTimeout(timerRef.current);
            unbindStart();
            unbindFinish();
            // Only remove the listener if it was actually registered.
            if (handleLoad) {
                window.removeEventListener('load', handleLoad);
            }
        };
    }, [minDelay]);

    if (!isLoading) {
        return null;
    }

    return (
        <div className="preloader" id="preloader">
            <div className="preloader">
                <div className="waviy position-relative">
                    {letters.map((char, index) => (
                        <span key={`${char}-${index}`} className="d-inline-block">
                            {char}
                        </span>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default Preloader;
