/**
 * Pick the message to show under a form field: the Zod error from the schema
 * first, and the error Laravel sent back for that field when the schema is happy.
 *
 * TanStack Form types the errors as `unknown[]` because a validator may return
 * anything; with a Zod schema each entry is an issue object carrying `message`.
 */
export function fieldError(errors: unknown[], serverError?: string): string | undefined {
    const [firstError] = errors;

    if (!firstError) {
        return serverError || undefined;
    }

    return typeof firstError === 'string' ? firstError : (firstError as { message?: string }).message;
}
