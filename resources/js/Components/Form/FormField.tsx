import { PropsWithChildren } from 'react';

interface FormFieldProps {
    /** Id of the input passed as children, so clicking the label focuses it. */
    htmlFor: string;
    label: string;
    /** Validation message to show under the input, if any. */
    error?: string;
    /** Extra note under the input, e.g. "Email cannot be changed.". */
    hint?: string;
}

/**
 * The label / input / error layout repeated by every field on the Account page.
 * The input itself is passed as children, so each form keeps full control of it.
 */
export default function FormField({ htmlFor, label, error, hint, children }: PropsWithChildren<FormFieldProps>) {
    return (
        <div className="form-group mb-4">
            <label htmlFor={htmlFor} className="label text-secondary">
                {label}
            </label>

            {children}

            {hint && <span className="d-block fs-14 text-secondary mt-1">{hint}</span>}
            {error && <div className="invalid-feedback d-block">{error}</div>}
        </div>
    );
}
