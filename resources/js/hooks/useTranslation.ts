import { useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import { translate, TranslateFn } from '@/lib/i18n';
import { Locale, PageProps } from '@/types';

/**
 * The current language and a t() bound to its dictionary. The dictionary is
 * an Inertia once prop, so it stays the same object until the language changes.
 */
export function useTranslation(): { t: TranslateFn; locale: Locale } {
    const { translations, locale } = usePage<PageProps>().props;

    const t = useCallback<TranslateFn>((key, replacements) => translate(translations, key, replacements), [translations]);

    return { t, locale };
}
