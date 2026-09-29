import { useState } from 'react';
import { useForm, useStore, revalidateLogic } from '@tanstack/react-form';
import { router, Link } from '@inertiajs/react';
import { loginSchema, LoginFormValues } from '@/Schemas';
import { fieldError } from '@/lib/fieldError';
import { useTranslation } from '@/hooks/useTranslation';

type LoginField = keyof LoginFormValues;

export default function LoginForm() {
    const { t } = useTranslation();
    const [serverErrors, setServerErrors] = useState<Partial<Record<LoginField, string>>>({});

    const form = useForm({
        defaultValues: {
            email: '',
            password: '',
            remember: false,
        } as LoginFormValues,
        validationLogic: revalidateLogic(),
        validators: {
            onDynamic: loginSchema,
        },
        onSubmit: ({ value }) => {
            setServerErrors({});
            return new Promise<void>((resolve) => {
                router.post('/login', value, {
                    onError: (errors) => setServerErrors(errors),
                    onFinish: () => resolve(),
                });
            });
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    const clearServerError = (field: LoginField) => setServerErrors((prev) => ({ ...prev, [field]: undefined }));

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();
                form.handleSubmit();
            }}
        >
            <form.Field name="email">
                {(field) => {
                    const error = fieldError(t, field.state.meta.errors, serverErrors.email);

                    return (
                        <div className="form-group mb-4">
                            <label htmlFor={field.name} className="label text-secondary">
                                {t('Email Address')}
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="email"
                                className={`form-control h-55${error ? ' is-invalid' : ''}`}
                                style={error ? { backgroundImage: 'none' } : undefined}
                                placeholder="example@finvo.com"
                                value={field.state.value}
                                onBlur={field.handleBlur}
                                onChange={(e) => {
                                    clearServerError('email');
                                    field.handleChange(e.target.value);
                                }}
                                autoComplete="username"
                            />
                            {error && <div className="invalid-feedback d-block">{error}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <form.Field name="password">
                {(field) => {
                    const error = fieldError(t, field.state.meta.errors, serverErrors.password);

                    return (
                        <div className="form-group mb-4">
                            <label htmlFor={field.name} className="label text-secondary">
                                {t('Password')}
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="password"
                                className={`form-control h-55${error ? ' is-invalid' : ''}`}
                                style={error ? { backgroundImage: 'none' } : undefined}
                                placeholder={t('Type password')}
                                value={field.state.value}
                                onBlur={field.handleBlur}
                                onChange={(e) => {
                                    clearServerError('password');
                                    field.handleChange(e.target.value);
                                }}
                                autoComplete="current-password"
                            />
                            {error && <div className="invalid-feedback d-block">{error}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <div className="form-group mb-4 d-flex align-items-center justify-content-between">
                <form.Field name="remember">
                    {(field) => (
                        <div className="form-check">
                            <input id={field.name} name={field.name} type="checkbox" className="form-check-input" checked={field.state.value} onChange={(e) => field.handleChange(e.target.checked)} />
                            <label htmlFor={field.name} className="form-check-label text-secondary">
                                {t('Remember Me')}
                            </label>
                        </div>
                    )}
                </form.Field>
                <Link href="/forgot-password" className="text-decoration-none text-primary fw-semibold">
                    {t('Forgot Password?')}
                </Link>
            </div>

            <div className="form-group mb-4">
                <button type="submit" disabled={isSubmitting} className="btn btn-primary fw-medium py-2 px-3 w-100">
                    <div className="d-flex align-items-center justify-content-center py-1">
                        <i className="material-symbols-outlined text-white fs-20 me-2">login</i>
                        <span>{isSubmitting ? t('Signing in…') : t('Login')}</span>
                    </div>
                </button>
            </div>

            <div className="form-group">
                <p>
                    {t("Don't have an account?")}{' '}
                    <Link href="/register" className="fw-medium text-primary text-decoration-none">
                        {t('Register')}
                    </Link>
                </p>
            </div>
        </form>
    );
}
