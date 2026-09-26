import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { PageProps } from '@/types';

/**
 * Turns the message the backend flashed after a create, update or delete into
 * a toast. Renders nothing itself; the <Toaster /> lives in app.tsx.
 */
export default function FlashToasts() {
    const { flash } = usePage<PageProps>().props;

    useEffect(() => {
        if (flash?.status) {
            toast.success(flash.status);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    return null;
}
