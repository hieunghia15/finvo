/**
 * Checks lang/vi.json against the translation keys used in the code.
 *
 * Keys are English source strings (docs/phases/phase-1.md §9), so a key
 * missing from vi.json does not break anything visibly: the UI silently shows
 * English. This script turns that into a CI failure.
 *
 * - Missing key (used in code, absent from vi.json) → error, exit 1.
 * - Empty translation                               → error, exit 1.
 * - Unused key (in vi.json, not found in code)       → warning only, since a
 *   key may be used through a variable the scanner cannot follow.
 *
 * Scanned: t('…') and trans('…') in resources/js, __('…') and trans('…') in app/.
 * Group keys such as 'auth.failed' live in lang/vi/*.php and are skipped.
 */
import { readdirSync, readFileSync } from 'node:fs';
import { extname, join } from 'node:path';

/**
 * Keys the scanner cannot see because they sit in PHP constants, which cannot
 * call __(). Keep in sync with App\Services\UserOnboardingService.
 */
const EXTRA_KEYS = ['Bank', 'Salary', 'Bonus', 'Other income', 'Food & Drinks', 'Transportation', 'Shopping', 'Housing', 'Bills', 'Entertainment', 'Health', 'Other expenses'];

const SOURCES = [
    { dir: 'resources/js', extensions: ['.ts', '.tsx'], functions: ['t', 'trans'] },
    { dir: 'app', extensions: ['.php'], functions: ['__', 'trans'] },
];

const GROUP_KEY = /^[a-z_]+(\.[a-z_]+)+$/;

function* walk(dir) {
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) {
            yield* walk(path);
        } else {
            yield path;
        }
    }
}

/** Literal first arguments of the given functions, single- or double-quoted. */
function extractKeys(source, functions) {
    const names = functions.map((name) => name.replace('_', '\\_')).join('|');
    const pattern = new RegExp(`(?<![\\w$>:])(?:${names})\\(\\s*(?:'((?:[^'\\\\]|\\\\.)*)'|"((?:[^"\\\\]|\\\\.)*)")`, 'g');
    const keys = [];

    for (const match of source.matchAll(pattern)) {
        const raw = match[1] ?? match[2];
        keys.push(raw.replace(/\\(.)/g, '$1'));
    }

    return keys;
}

const used = new Map(EXTRA_KEYS.map((key) => [key, 'scripts/lang-check.mjs (EXTRA_KEYS)']));

for (const { dir, extensions, functions } of SOURCES) {
    for (const file of walk(dir)) {
        if (!extensions.includes(extname(file))) {
            continue;
        }

        for (const key of extractKeys(readFileSync(file, 'utf8'), functions)) {
            if (!GROUP_KEY.test(key) && !used.has(key)) {
                used.set(key, file);
            }
        }
    }
}

const dictionary = JSON.parse(readFileSync('lang/vi.json', 'utf8'));
const missing = [...used].filter(([key]) => !(key in dictionary));
const empty = Object.entries(dictionary).filter(([, value]) => typeof value !== 'string' || value.trim() === '');
const unused = Object.keys(dictionary).filter((key) => !used.has(key));

for (const [key, file] of missing) {
    console.error(`✗ missing in lang/vi.json: "${key}"  (${file})`);
}
for (const [key] of empty) {
    console.error(`✗ empty translation in lang/vi.json: "${key}"`);
}
for (const key of unused) {
    console.warn(`! unused in code: "${key}"`);
}

console.log(`lang:check — ${used.size} keys used, ${Object.keys(dictionary).length} translated, ${missing.length} missing, ${empty.length} empty, ${unused.length} unused.`);

process.exit(missing.length > 0 || empty.length > 0 ? 1 : 0);
