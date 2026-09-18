/**
 * Pick the message to show for a TanStack Form field: client-side schema
 * errors first, then any error returned by the server for that field.
 */
export function fieldError(errors: unknown[], serverError?: string): string | undefined {
    const [error] = errors;
    if (error) {
        return typeof error === 'string' ? error : (error as { message?: string }).message;
    }
    return serverError || undefined;
}
