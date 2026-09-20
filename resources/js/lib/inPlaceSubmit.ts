/**
 * The app has three kinds of loading indicator, and this file decides between
 * the first two:
 *
 * 1. Preloader     — full-page "FINVO" screen: first page load and page navigation.
 * 2. LoadingOverlay — blurred overlay with a spinner: a form submitted in place.
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

/**
 * Whether a visit was sent with IN_PLACE_SUBMIT. Only non-GET visits count,
 * because Inertia also turns progress off for its own GET prefetch and polling.
 */
export function isInPlaceSubmit(visit: { method: string; showProgress: boolean }): boolean {
    return visit.method !== 'get' && !visit.showProgress;
}
