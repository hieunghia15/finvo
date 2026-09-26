import { Locale, Translations } from '@/types';

/**
 * Translation keys are the English source strings (docs/phases/phase-1.md §9),
 * the same keys Laravel's __() uses, so English needs no dictionary at all.
 */

export type Replacements = Record<string, string | number>;

export type TranslateFn = (key: string, replacements?: Replacements) => string;

/** Languages offered by LanguageSwitcher, each named in its own language. */
export const LOCALES: ReadonlyArray<{ code: Locale; name: string; flag: string }> = [
    { code: 'vi', name: 'Tiếng Việt', flag: '/assets/trezo/images/vietnam.svg' },
    { code: 'en', name: 'English', flag: '/assets/trezo/images/usa.svg' },
];

/**
 * Intl locale for dates and numbers in each UI language. English uses en-GB so
 * dates keep the day/month/year order Vietnamese users read.
 */
export const INTL_LOCALES: Record<Locale, string> = { vi: 'vi-VN', en: 'en-GB' };

/**
 * Marks an English source string for `npm run lang:check` without translating
 * it. For module-level strings where t() cannot run: Zod schemas, label maps
 * and menu config. The component that shows the string passes it through t().
 */
export function trans(key: string): string {
    return key;
}

/**
 * Look the key up and fill Laravel-style `:name` placeholders. A missing key
 * falls back to the English key itself, like Laravel does.
 */
export function translate(translations: Translations, key: string, replacements?: Replacements): string {
    let text = translations[key] ?? key;

    if (!replacements) {
        return text;
    }

    // Longest names first, so ":name" does not eat into ":names".
    const names = Object.keys(replacements).sort((a, b) => b.length - a.length);

    for (const name of names) {
        text = text.replaceAll(`:${name}`, String(replacements[name]));
    }

    return text;
}
