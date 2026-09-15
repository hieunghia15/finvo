import { useState } from 'react';
import { useForm } from '@tanstack/react-form';
import { router, Link } from '@inertiajs/react';
import { loginSchema } from '@/Schemas';
import type { LoginFormValues } from '@/Schemas';
import { useAuth } from '@/features/auth/hooks';
import type { AxiosError } from 'axios';

interface ServerErrors {
    email?: string;
    password?: string;
    general?: string;
}

export default function LoginForm() {
    const [serverErrors, setServerErrors] = useState<ServerErrors>({});
    const { login } = useAuth();

    const form = useForm({
        defaultValues: {
            email: '',
            password: '',
        } as LoginFormValues,
        onSubmit: async ({ value }) => {
            setServerErrors({});
            try {
                await login({ email: value.email, password: value.password });
                router.visit('/dashboard');
            } catch (error: unknown) {
                const axiosError = error as AxiosError<{
                    message?: string;
                    errors?: Record<string, string[]>;
                }>;
                const responseData = axiosError?.response?.data;
                if (responseData?.errors) {
                    const mapped: ServerErrors = {};
                    for (const [key, messages] of Object.entries(responseData.errors)) {
                        if (key === 'email') mapped.email = messages[0];
                        else if (key === 'password') mapped.password = messages[0];
                    }
                    setServerErrors(mapped);
                } else {
                    setServerErrors({
                        general: responseData?.message ?? 'An unexpected error occurred. Please try again.',
                    });
                }
            }
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
            {serverErrors.general && (
                <div className="alert alert-danger mb-4" role="alert">
                    {serverErrors.general}
                </div>
            )}

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
                    const fieldError = field.state.meta.errors.length ? field.state.meta.errors.join(', ') : serverErrors.email;

                    return (
                        <div className="form-group mb-4">
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
                                    setServerErrors((prev) => ({ ...prev, email: undefined }));
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
                        const res = loginSchema.shape.password.safeParse(value);
                        return res.success ? undefined : res.error.issues[0]?.message;
                    },
                }}
            >
                {(field) => {
                    const fieldError = field.state.meta.errors.length ? field.state.meta.errors.join(', ') : serverErrors.password;

                    return (
                        <div className="form-group mb-4">
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
                                        setServerErrors((prev) => ({ ...prev, password: undefined }));
                                        field.handleChange(e.target.value);
                                    }}
                                />
                            {fieldError && <div className="invalid-feedback d-block">{fieldError}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <div className="form-group mb-4">
                <Link href="/forgot-password" className="text-decoration-none text-primary fw-semibold">
                    Forgot Password?
                </Link>
            </div>

            <form.Subscribe selector={(state) => [state.isSubmitting, state.canSubmit]}>
                {([isSubmitting, canSubmit]) => (
                    <div className="form-group mb-4">
                        <button type="submit" disabled={isSubmitting || !canSubmit} className="btn btn-primary fw-medium py-2 px-3 w-100">
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
