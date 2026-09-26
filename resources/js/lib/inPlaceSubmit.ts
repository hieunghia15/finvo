/**
 * The app has three kinds of loading indicator, and this file decides between
 * the first two:
 *
 * 1. Preloader     — full-page "FINVO" screen: first page load and page navigation.
 * 2. LoadingOverlay — blurred overlay with a spinner: a form submitted in place,
 *                     or a list re-read with other filters (IN_PLACE_FILTER).
 * 3. Submit button  — "Saving…" / "Changing…": comes from the form's own
 *                     isSubmitting, and is handled inside each form.
 */

/**
 * Visit options for a form that updates the current page in place: the page
 * keeps its state and scroll position, and LoadingOverlay is shown instead of
 * the full-page Preloader. Login, register and logout do not use these.
 */
export const IN_PLACE_SUBMIT = {
    preserveScroll: true,
    preserveState: true,
    showProgress: false,
} as const;

/** Marks a GET visit that only refreshes the current page, e.g. a list filter. */
export const IN_PLACE_HEADER = 'X-Finvo-In-Place';

/**
 * Visit options for a GET that re-reads the current page with other filters:
 * LoadingOverlay instead of Preloader, and no new history entry per click.
 */
export const IN_PLACE_FILTER = {
    preserveScroll: true,
    preserveState: true,
    replace: true,
    showProgress: false,
    headers: { [IN_PLACE_HEADER]: '1' },
} as const;

/**
 * Whether a visit was sent with IN_PLACE_SUBMIT or IN_PLACE_FILTER.
 */
export function isInPlaceSubmit(visit: { method: string; showProgress: boolean; headers: Record<string, string> }): boolean {
    if (visit.showProgress) {
        return false;
    }

    // Inertia's own prefetch and polling are GETs without progress too, so a
    // GET only counts when it was explicitly marked with IN_PLACE_FILTER.
    return visit.method !== 'get' || visit.headers[IN_PLACE_HEADER] === '1';
}
