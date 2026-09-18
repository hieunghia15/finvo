import { useState } from 'react';
import { useForm, revalidateLogic } from '@tanstack/react-form';
import { router, Link } from '@inertiajs/react';
import { registerSchema, RegisterFormValues, fieldError } from '@/Schemas';

type RegisterField = keyof RegisterFormValues;

export default function RegisterForm() {
    const [serverErrors, setServerErrors] = useState<Partial<Record<RegisterField, string>>>({});

    const form = useForm({
        defaultValues: {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
        } as RegisterFormValues,
        validationLogic: revalidateLogic(),
        validators: {
            onDynamic: registerSchema,
        },
        onSubmit: ({ value }) => {
            setServerErrors({});
            return new Promise<void>((resolve) => {
                router.post('/register', value, {
                    onError: (errors) => setServerErrors(errors),
                    onFinish: () => resolve(),
                });
            });
        },
    });

    const clearServerError = (field: RegisterField) => setServerErrors((prev) => ({ ...prev, [field]: undefined }));

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();
                form.handleSubmit();
            }}
        >
            <form.Field name="name">
                {(field) => {
                    const error = fieldError(field.state.meta.errors, serverErrors.name);

                    return (
                        <div className="form-group mb-3">
                            <label htmlFor={field.name} className="label text-secondary">
                                Full Name
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="text"
                                className={`form-control h-55${error ? ' is-invalid' : ''}`}
                                style={error ? { backgroundImage: 'none' } : undefined}
                                placeholder="Enter your full name"
                                value={field.state.value}
                                onBlur={field.handleBlur}
                                onChange={(e) => {
                                    clearServerError('name');
                                    field.handleChange(e.target.value);
                                }}
                                autoComplete="name"
                            />
                            {error && <div className="invalid-feedback d-block">{error}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <form.Field name="email">
                {(field) => {
                    const error = fieldError(field.state.meta.errors, serverErrors.email);

                    return (
                        <div className="form-group mb-3">
                            <label htmlFor={field.name} className="label text-secondary">
                                Email Address
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="email"
                                className={`form-control h-55${error ? ' is-invalid' : ''}`}
                                style={error ? { backgroundImage: 'none' } : undefined}
                                placeholder="example@trezo.com"
                                value={field.state.value}
                                onBlur={field.handleBlur}
                                onChange={(e) => {
                                    clearServerError('email');
                                    field.handleChange(e.target.value);
                                }}
                                autoComplete="email"
                            />
                            {error && <div className="invalid-feedback d-block">{error}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <form.Field name="password">
                {(field) => {
                    const error = fieldError(field.state.meta.errors, serverErrors.password);

                    return (
                        <div className="form-group mb-3">
                            <label htmlFor={field.name} className="label text-secondary">
                                Password
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="password"
                                className={`form-control h-55${error ? ' is-invalid' : ''}`}
                                style={error ? { backgroundImage: 'none' } : undefined}
                                placeholder="Type password"
                                value={field.state.value}
                                onBlur={field.handleBlur}
                                onChange={(e) => {
                                    clearServerError('password');
                                    field.handleChange(e.target.value);
                                }}
                                autoComplete="new-password"
                            />
                            {error && <div className="invalid-feedback d-block">{error}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <form.Field name="password_confirmation">
                {(field) => {
                    const error = fieldError(field.state.meta.errors, serverErrors.password_confirmation);

                    return (
                        <div className="form-group mb-3">
                            <label htmlFor={field.name} className="label text-secondary">
                                Confirm Password
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="password"
                                className={`form-control h-55${error ? ' is-invalid' : ''}`}
                                style={error ? { backgroundImage: 'none' } : undefined}
                                placeholder="Retype password"
                                value={field.state.value}
                                onBlur={field.handleBlur}
                                onChange={(e) => {
                                    clearServerError('password_confirmation');
                                    field.handleChange(e.target.value);
                                }}
                                autoComplete="new-password"
                            />
                            {error && <div className="invalid-feedback d-block">{error}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <form.Subscribe selector={(state) => state.isSubmitting}>
                {(isSubmitting) => (
                    <div className="form-group mb-3">
                        <button type="submit" disabled={isSubmitting} className="btn btn-primary fw-medium py-2 px-3 w-100">
                            <div className="d-flex align-items-center justify-content-center py-1">
                                <i className="material-symbols-outlined text-white fs-20 me-2">person_4</i>
                                <span>{isSubmitting ? 'Registering…' : 'Register'}</span>
                            </div>
                        </button>
                    </div>
                )}
            </form.Subscribe>

            <div className="form-group">
                <p>
                    By registering, you agree to our{' '}
                    <a href="#" className="fw-medium text-decoration-none">
                        Terms of Service
                    </a>{' '}
                    and that you have read and understood our{' '}
                    <a href="#" className="fw-medium text-decoration-none">
                        Privacy Policy
                    </a>
                    .
                </p>
                <p>
                    Already have an account.{' '}
                    <Link href="/" className="fw-medium text-primary text-decoration-none">
                        Log In
                    </Link>
                </p>
            </div>
        </form>
    );
}
