import { PropsWithChildren, ReactNode, useEffect, useId } from 'react';
import { createPortal } from 'react-dom';
import { useTranslation } from '@/hooks/useTranslation';

interface ModalProps {
    title: string;
    onClose: () => void;
    /** Disables the × button, e.g. while submitting. */
    isCloseDisabled?: boolean;
    size?: 'sm' | 'lg';
    /** Buttons rendered in `.modal-footer`. */
    footer?: ReactNode;
}

/**
 * Bootstrap modal markup driven by React instead of bootstrap.bundle.js.
 *
 * There is no isOpen prop: the parent mounts the modal to open it and
 * unmounts it to close it, so every opening starts from a fresh state.
 *
 * Only the × button (or a Cancel button the caller puts in the footer) closes
 * it. A click on the backdrop or the Esc key does not, so a half-filled form is
 * never lost by accident.
 */
export default function Modal({ title, onClose, isCloseDisabled = false, size, footer, children }: PropsWithChildren<ModalProps>) {
    const { t } = useTranslation();
    const titleId = useId();

    // Lock the page scroll behind the modal, as Bootstrap does.
    useEffect(() => {
        document.body.classList.add('modal-open');

        return () => document.body.classList.remove('modal-open');
    }, []);

    // Portalled to <body>: the page container has overflow-hidden.
    return createPortal(
        <>
            <div className="modal fade show d-block" tabIndex={-1} role="dialog" aria-modal="true" aria-labelledby={titleId}>
                <div className={`modal-dialog modal-dialog-centered${size ? ` modal-${size}` : ''}`}>
                    <div className="modal-content">
                        <div className="modal-header">
                            <h1 className="modal-title fs-5" id={titleId}>
                                {title}
                            </h1>
                            <button type="button" className="btn-close" aria-label={t('Close')} disabled={isCloseDisabled} onClick={onClose}></button>
                        </div>

                        <div className="modal-body">{children}</div>

                        {footer && <div className="modal-footer">{footer}</div>}
                    </div>
                </div>
            </div>
            <div className="modal-backdrop fade show"></div>
        </>,
        document.body
    );
}
