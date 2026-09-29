import { TranslateFn } from '@/lib/i18n';

/**
 * Pick the message to show under a form field: the Zod error from the schema
 * first, and the error Laravel sent back for that field when the schema is happy.
 *
 * Zod messages are English source strings (marked with trans() in the schema),
 * so they are translated here. Laravel's messages arrive already translated
 * and are shown as they are.
 *
 * TanStack Form types the errors as `unknown[]` because a validator may return
 * anything; with a Zod schema each entry is an issue object carrying `message`.
 */
export function fieldError(t: TranslateFn, errors: unknown[], serverError?: string): string | undefined {
    const [firstError] = errors;

    if (!firstError) {
        return serverError || undefined;
    }

    const message = typeof firstError === 'string' ? firstError : (firstError as { message?: string }).message;

    return message === undefined ? undefined : t(message);
}
