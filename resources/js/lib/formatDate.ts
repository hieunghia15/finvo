import { INTL_LOCALES } from '@/lib/i18n';
import { Locale } from '@/types';

const DATE_FORMATS: Record<Locale, Intl.DateTimeFormat> = {
    vi: createFormat('vi'),
    en: createFormat('en'),
};

function createFormat(locale: Locale): Intl.DateTimeFormat {
    return new Intl.DateTimeFormat(INTL_LOCALES[locale], {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'Asia/Ho_Chi_Minh',
    });
}

/**
 * Format an ISO 8601 date from the backend as dd/MM/yyyy in Vietnam time,
 * with the separators of the UI language.
 *
 * Laravel serializes dates with microseconds ("…T03:00:00.000000Z"), but the
 * JS Date parser only guarantees milliseconds, so the fraction is trimmed.
 */
export function formatDate(iso: string, locale: Locale): string {
    const date = new Date(iso.replace(/(\.\d{3})\d+/, '$1'));
    return Number.isNaN(date.getTime()) ? '—' : DATE_FORMATS[locale].format(date);
}
