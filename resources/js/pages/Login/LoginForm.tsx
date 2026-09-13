import { useState } from 'react';
import { useForm } from '@tanstack/react-form';
import { router, Link } from '@inertiajs/react';
import { z } from 'zod';

const loginSchema = z.object({
    email: z
        .string()
        .min(1, 'Email is required')
        .email('Please enter a valid email address'),
    password: z
        .string()
        .min(1, 'Password is required'),
    remember: z.boolean(),
});

type LoginFormValues = z.infer<typeof loginSchema>;

export default function LoginForm() {
    const [showPassword, setShowPassword] = useState(false);
    const [serverErrors, setServerErrors] = useState<Record<string, string>>({});

    const form = useForm({
        defaultValues: {
            email: '',
            password: '',
            remember: false,
        } as LoginFormValues,
        validators: {
            onChange: ({ value }) => {
                const result = loginSchema.safeParse(value);
                if (!result.success) {
                    return result.error.issues[0]?.message;
                }
                return undefined;
            },
        },
        onSubmit: async ({ value }) => {
            setServerErrors({});
            return new Promise<void>((resolve) => {
                router.post('/login', value, {
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
                name="email"
                validators={{
                    onChange: ({ value }) => {
                        const res = loginSchema.shape.email.safeParse(value);
                        return res.success ? undefined : res.error.issues[0]?.message;
                    },
                }}
            >
                {(field) => {
                    const fieldError = field.state.meta.errors.length
                        ? field.state.meta.errors.join(', ')
                        : serverErrors.email;

                    return (
                        <div className="form-group mb-4">
                            <label htmlFor={field.name} className="label text-secondary">
                                Email Address
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="text"
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
                            {fieldError && (
                                <div className="invalid-feedback">{fieldError}</div>
                            )}
                        </div>
                    );
                }}
            </form.Field>

            <form.Field
                name="password"
                validators={{
                    onChange: ({ value }) => {
                        const res = loginSchema.shape.password.safeParse(value);
                        return res.success ? undefined : res.error.issues[0]?.message;
                    },
                }}
            >
                {(field) => {
                    const fieldError = field.state.meta.errors.length
                        ? field.state.meta.errors.join(', ')
                        : serverErrors.password;

                    return (
                        <div className="form-group mb-4">
                            <label htmlFor={field.name} className="label text-secondary">
                                Password
                            </label>
                            <div className="position-relative">
                                <input
                                    id={field.name}
                                    name={field.name}
                                    type={showPassword ? 'text' : 'password'}
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
                                <button
                                    type="button"
                                    className="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3 text-secondary"
                                    onClick={() => setShowPassword((v) => !v)}
                                    tabIndex={-1}
                                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                                >
                                    <i className="material-symbols-outlined fs-20">
                                        {showPassword ? 'visibility_off' : 'visibility'}
                                    </i>
                                </button>
                            </div>
                            {fieldError && (
                                <div className="invalid-feedback d-block">{fieldError}</div>
                            )}
                        </div>
                    );
                }}
            </form.Field>

            <div className="form-group mb-4">
                <Link
                    href="/forgot-password"
                    className="text-decoration-none text-primary fw-semibold"
                >
                    Forgot Password?
                </Link>
            </div>

            <form.Subscribe
                selector={(state) => [state.isSubmitting, state.canSubmit]}
            >
                {([isSubmitting]) => (
                    <div className="form-group mb-4">
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="btn btn-primary fw-medium py-2 px-3 w-100"
                        >
                            <div className="d-flex align-items-center justify-content-center py-1">
                                <i className="material-symbols-outlined text-white fs-20 me-2">login</i>
                                <span>{isSubmitting ? 'Signing in…' : 'Login'}</span>
                            </div>
                        </button>
                    </div>
                )}
            </form.Subscribe>

            <div className="form-group">
                <p>
                    Don't have an account?{' '}
                    <Link href="/register" className="fw-medium text-primary text-decoration-none">
                        Register
                    </Link>
                </p>
            </div>
        </form>
    );
}


