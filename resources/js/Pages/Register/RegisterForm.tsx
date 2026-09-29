import { useState } from 'react';
import { useForm, useStore, revalidateLogic } from '@tanstack/react-form';
import { router, Link } from '@inertiajs/react';
import { registerSchema, RegisterFormValues } from '@/Schemas';
import { fieldError } from '@/lib/fieldError';
import { useTranslation } from '@/hooks/useTranslation';

type RegisterField = keyof RegisterFormValues;

export default function RegisterForm() {
    const { t } = useTranslation();
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

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

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
                    const error = fieldError(t, field.state.meta.errors, serverErrors.name);

                    return (
                        <div className="form-group mb-3">
                            <label htmlFor={field.name} className="label text-secondary">
                                {t('Full Name')}
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="text"
                                className={`form-control h-55${error ? ' is-invalid' : ''}`}
                                style={error ? { backgroundImage: 'none' } : undefined}
                                placeholder={t('Enter your full name')}
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
                    const error = fieldError(t, field.state.meta.errors, serverErrors.email);

                    return (
                        <div className="form-group mb-3">
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
                                autoComplete="email"
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
                        <div className="form-group mb-3">
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
                                autoComplete="new-password"
                            />
                            {error && <div className="invalid-feedback d-block">{error}</div>}
                        </div>
                    );
                }}
            </form.Field>

            <form.Field name="password_confirmation">
                {(field) => {
                    const error = fieldError(t, field.state.meta.errors, serverErrors.password_confirmation);

                    return (
                        <div className="form-group mb-3">
                            <label htmlFor={field.name} className="label text-secondary">
                                {t('Confirm Password')}
                            </label>
                            <input
                                id={field.name}
                                name={field.name}
                                type="password"
                                className={`form-control h-55${error ? ' is-invalid' : ''}`}
                                style={error ? { backgroundImage: 'none' } : undefined}
                                placeholder={t('Retype password')}
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

            <div className="form-group mb-3">
                <button type="submit" disabled={isSubmitting} className="btn btn-primary fw-medium py-2 px-3 w-100">
                    <div className="d-flex align-items-center justify-content-center py-1">
                        <i className="material-symbols-outlined text-white fs-20 me-2">person_4</i>
                        <span>{isSubmitting ? t('Registering…') : t('Register')}</span>
                    </div>
                </button>
            </div>

            <div className="form-group">
                <p>
                    {t('By registering, you agree to our')}{' '}
                    <a href="#" className="fw-medium text-decoration-none">
                        {t('Terms of Service')}
                    </a>{' '}
                    {t('and that you have read and understood our')}{' '}
                    <a href="#" className="fw-medium text-decoration-none">
                        {t('Privacy Policy')}
                    </a>
                    .
                </p>
                <p>
                    {t('Already have an account?')}{' '}
                    <Link href="/" className="fw-medium text-primary text-decoration-none">
                        {t('Log In')}
                    </Link>
                </p>
            </div>
        </form>
    );
}
