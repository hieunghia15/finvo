import { useState } from 'react';
import { useForm } from '@tanstack/react-form';
import { router, Link } from '@inertiajs/react';
import { registerSchema, RegisterFormValues } from '@/Schemas';

export default function RegisterForm() {
    const [serverErrors, setServerErrors] = useState<Record<string, string>>({});

    const form = useForm({
        defaultValues: {
            name: '',
            email: '',
            password: '',
        } as RegisterFormValues,
        validators: {
            onChange: ({ value }) => {
                const result = registerSchema.safeParse(value);
                if (!result.success) {
                    return result.error.issues[0]?.message;
                }
                return undefined;
            },
        },
        onSubmit: async ({ value }) => {
            setServerErrors({});
            return new Promise<void>((resolve) => {
                router.post('/register', value, {
                    onError: (errors) => {
                        setServerErrors(errors);
                        resolve();
                    },
                    onFinish: () => {
                        resolve();
                    },
                });
            });
        },
    });

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();
                form.handleSubmit();
            }}
        >
            <form.Field
                name="name"
                validators={{
                    onChange: ({ value }) => {
                        const res = registerSchema.shape.name.safeParse(value);
                        return res.success ? undefined : res.error.issues[0]?.message;
                    },
                }}
            >
                {(field) => {
                    const fieldError = field.state.meta.errors.length ? field.state.meta.errors.join(', ') : serverErrors.name;

                    return (
                        <div className="form-group mb-3">
                            <label htmlFor={field.name} className="label text-secondary">
                                Full Name
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="text"
                                className={`form-control h-55${fieldError ? ' is-invalid' : ''}`}
                                style={fieldError ? { backgroundImage: 'none' } : undefined}
                                placeholder="Enter your full name"
                                value={field.state.value}
                                onBlur={field.handleBlur}
                                onChange={(e) => {
                                    setServerErrors((prev) => ({ ...prev, name: '' }));
                                    field.handleChange(e.target.value);
                                }}
                            />
                            {fieldError && <div className="invalid-feedback">{fieldError}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <form.Field
                name="email"
                validators={{
                    onChange: ({ value }) => {
                        const res = registerSchema.shape.email.safeParse(value);
                        return res.success ? undefined : res.error.issues[0]?.message;
                    },
                }}
            >
                {(field) => {
                    const fieldError = field.state.meta.errors.length ? field.state.meta.errors.join(', ') : serverErrors.email;

                    return (
                        <div className="form-group mb-3">
                            <label htmlFor={field.name} className="label text-secondary">
                                Email Address
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="email"
                                className={`form-control h-55${fieldError ? ' is-invalid' : ''}`}
                                style={fieldError ? { backgroundImage: 'none' } : undefined}
                                placeholder="example@trezo.com"
                                value={field.state.value}
                                onBlur={field.handleBlur}
                                onChange={(e) => {
                                    setServerErrors((prev) => ({ ...prev, email: '' }));
                                    field.handleChange(e.target.value);
                                }}
                            />
                            {fieldError && <div className="invalid-feedback">{fieldError}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <form.Field
                name="password"
                validators={{
                    onChange: ({ value }) => {
                        const res = registerSchema.shape.password.safeParse(value);
                        return res.success ? undefined : res.error.issues[0]?.message;
                    },
                }}
            >
                {(field) => {
                    const fieldError = field.state.meta.errors.length ? field.state.meta.errors.join(', ') : serverErrors.password;

                    return (
                        <div className="form-group mb-3">
                            <label htmlFor={field.name} className="label text-secondary">
                                Password
                            </label>
                            <input
                                    id={field.name}
                                    name={field.name}
                                    type="password"
                                    className={`form-control h-55${fieldError ? ' is-invalid' : ''}`}
                                    style={fieldError ? { backgroundImage: 'none' } : undefined}
                                    placeholder="Type password"
                                    value={field.state.value}
                                    onBlur={field.handleBlur}
                                    onChange={(e) => {
                                    setServerErrors((prev) => ({ ...prev, password: '' }));
                                        field.handleChange(e.target.value);
                                    }}
                                />
                            {fieldError && <div className="invalid-feedback d-block">{fieldError}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <form.Subscribe selector={(state) => [state.isSubmitting, state.canSubmit]}>
                {([isSubmitting, canSubmit]) => (
                    <div className="form-group mb-3">
                        <button type="submit" disabled={isSubmitting || !canSubmit} className="btn btn-primary fw-medium py-2 px-3 w-100">
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
                    By confirming your email, you agree to our{' '}
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
