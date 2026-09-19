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
