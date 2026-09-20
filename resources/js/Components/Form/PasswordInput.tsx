import { useState } from 'react';

export interface PasswordInputProps {
    id: string;
    name: string;
    value: string;
    onChange: (value: string) => void;
    onBlur: () => void;
    autoComplete: 'current-password' | 'new-password';
    placeholder?: string;
    isInvalid?: boolean;
}

/**
 * Password field with a show/hide toggle. The label and error message are
 * rendered by the parent form.
 */
export default function PasswordInput({ id, name, value, onChange, onBlur, autoComplete, placeholder, isInvalid = false }: PasswordInputProps) {
    const [isVisible, setIsVisible] = useState(false);

    return (
        <div className="password-wrapper position-relative">
            <input
                id={id}
                name={name}
                type={isVisible ? 'text' : 'password'}
                className={`form-control h-55 text-dark pe-5${isInvalid ? ' is-invalid' : ''}`}
                // Bootstrap's invalid icon would sit under the toggle button.
                style={isInvalid ? { backgroundImage: 'none' } : undefined}
                placeholder={placeholder}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                onBlur={onBlur}
                autoComplete={autoComplete}
            />
            <button
                type="button"
                className="btn p-0 border-0 bg-transparent position-absolute top-50 translate-middle-y lh-1"
                style={{ right: 15, color: '#A9A9C8' }}
                onClick={() => setIsVisible((prev) => !prev)}
                aria-label={isVisible ? 'Hide password' : 'Show password'}
            >
                <i className={`${isVisible ? 'ri-eye-line' : 'ri-eye-off-line'} fs-16`} aria-hidden="true"></i>
            </button>
        </div>
    );
}
