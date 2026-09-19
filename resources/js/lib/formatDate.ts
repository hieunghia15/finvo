const DATE_FORMAT = new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    timeZone: 'Asia/Ho_Chi_Minh',
});

/**
 * Format an ISO 8601 date from the backend as dd/MM/yyyy in Vietnam time.
 *
 * Laravel serializes dates with microseconds ("…T03:00:00.000000Z"), but the
 * JS Date parser only guarantees milliseconds, so the fraction is trimmed.
 */
export function formatDate(iso: string): string {
    const date = new Date(iso.replace(/(\.\d{3})\d+/, '$1'));
    return Number.isNaN(date.getTime()) ? '—' : DATE_FORMAT.format(date);
}
