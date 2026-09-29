import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { useTranslation } from '@/hooks/useTranslation';
import { LOCALES } from '@/lib/i18n';
import { Locale } from '@/types';

/**
 * "Choose Language" dropdown from the Trezo template (multi-lang.html), driven
 * by React state like the other header dropdowns instead of Bootstrap's JS.
 *
 * It relies on the template's `.right-header-content .header-right-item
 * .notifications.dropdown` styles, so the caller renders it inside that markup.
 */
export default function LanguageSwitcher() {
    const { t, locale } = useTranslation();
    const [isOpen, setIsOpen] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        const handleClickOutside = (event: MouseEvent) => {
            if (rootRef.current && !rootRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen]);

    const choose = (code: Locale) => {
        setIsOpen(false);

        if (code === locale) {
            return;
        }

        // A plain visit, not IN_PLACE_SUBMIT: every word on the page changes, so
        // the full-page Preloader fits better than the in-place overlay.
        router.put('/locale', { locale: code });
    };

    return (
        <div ref={rootRef} className="dropdown notifications language position-relative">
            <button
                type="button"
                className="btn btn-secondary dropdown-toggle border-0 p-0 position-relative"
                onClick={() => setIsOpen((previous) => !previous)}
                aria-expanded={isOpen}
                aria-label={t('Choose Language')}
                title={t('Choose Language')}
            >
                <span className="material-symbols-outlined">translate</span>
            </button>

            {isOpen && (
                <div className="dropdown-menu dropdown-lg p-0 border-0 dropdown-menu-end show d-block position-absolute end-0 mt-2" style={{ inset: '0px 0px auto auto', margin: '0px' }}>
                    <span className="fw-semibold fs-15 text-secondary title">{t('Choose Language')}</span>

                    {LOCALES.map((option, index) => {
                        const isCurrent = option.code === locale;

                        return (
                            <div key={option.code} className={`notification-menu${index === LOCALES.length - 1 ? ' mb-0' : ''}`}>
                                <button
                                    type="button"
                                    className="dropdown-item w-100 border-0 bg-transparent text-start"
                                    aria-current={isCurrent ? 'true' : undefined}
                                    onClick={() => choose(option.code)}
                                >
                                    <div className="d-flex align-items-center">
                                        <div className="flex-shrink-0">
                                            <img src={option.flag} className="wh-30 rounded-circle" alt="" />
                                        </div>
                                        <div className="flex-grow-1 ms-2">
                                            <span className={`text-secondary fs-14 ${isCurrent ? 'fw-semibold' : 'fw-medium'}`}>{option.name}</span>
                                        </div>
                                        {isCurrent && <span className="material-symbols-outlined text-primary fs-18">check</span>}
                                    </div>
                                </button>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
