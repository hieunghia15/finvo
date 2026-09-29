/** Mirrors App\Enums\Locale. */
export type Locale = 'vi' | 'en';

/** Flat dictionary from lang/vi.json: English source string → translation. Empty for English. */
export type Translations = Record<string, string>;
