import { PropsWithChildren, ReactNode, useEffect, useId } from 'react';
import { createPortal } from 'react-dom';

interface ModalProps {
    title: string;
    onClose: () => void;
    /** Blocks Esc, backdrop click and the × button, e.g. while submitting. */
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
 */
export default function Modal({ title, onClose, isCloseDisabled = false, size, footer, children }: PropsWithChildren<ModalProps>) {
    const titleId = useId();

    // Lock the page scroll behind the modal, as Bootstrap does.
    useEffect(() => {
        document.body.classList.add('modal-open');

        return () => document.body.classList.remove('modal-open');
    }, []);

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && !isCloseDisabled) {
                onClose();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [isCloseDisabled, onClose]);

    // Portalled to <body>: the page container has overflow-hidden.
    return createPortal(
        <>
            <div
                className="modal fade show d-block"
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                onClick={(event) => {
                    // Only a click on the dimmed area around the dialog closes it.
                    if (event.target === event.currentTarget && !isCloseDisabled) {
                        onClose();
                    }
                }}
            >
                <div className={`modal-dialog modal-dialog-centered${size ? ` modal-${size}` : ''}`}>
                    <div className="modal-content">
                        <div className="modal-header">
                            <h1 className="modal-title fs-5" id={titleId}>
                                {title}
                            </h1>
                            <button type="button" className="btn-close" aria-label="Close" disabled={isCloseDisabled} onClick={onClose}></button>
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
