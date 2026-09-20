import { useState } from 'react';
import { useForm, useStore, revalidateLogic } from '@tanstack/react-form';
import { router } from '@inertiajs/react';
import FormField from '@/Components/Form/FormField';
import PasswordInput from '@/Components/Form/PasswordInput';
import { updatePasswordSchema } from '@/Schemas';
import { fieldError } from '@/lib/fieldError';
import { IN_PLACE_SUBMIT } from '@/lib/inPlaceSubmit';

/**
 * Validation errors Laravel sends back. `password_confirmation` is included for
 * completeness; the backend `confirmed` rule reports a mismatch on `password`.
 */
type PasswordServerErrors = {
    current_password?: string;
    password?: string;
    password_confirmation?: string;
};

/**
 * Change Password tab. The three values only live in the form state and are
 * cleared as soon as the password has been changed.
 */
export default function ChangePasswordForm() {
    const [serverErrors, setServerErrors] = useState<PasswordServerErrors>({});

    const form = useForm({
        defaultValues: {
            current_password: '',
            password: '',
            password_confirmation: '',
        },
        validationLogic: revalidateLogic(),
        validators: { onDynamic: updatePasswordSchema },
        onSubmit: ({ value, formApi }) => {
            setServerErrors({});

            // router.put has no promise to await, so wrap it in one and resolve
            // on onFinish; otherwise isSubmitting would flip back immediately.
            return new Promise<void>((resolve) => {
                // IN_PLACE_SUBMIT keeps this tab open after the redirect back to
                // /account; without it the page remounts and falls back to Account.
                router.put('/account/password', value, {
                    ...IN_PLACE_SUBMIT,
                    onSuccess: () => formApi.reset(),
                    onError: (errors) => setServerErrors(errors),
                    onFinish: () => resolve(),
                });
            });
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    const clearServerError = (field: keyof PasswordServerErrors) => {
        setServerErrors((previous) => ({ ...previous, [field]: undefined }));
    };

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();
                form.handleSubmit();
            }}
        >
            <div className="row">
                <div className="col-lg-6">
                    <form.Field name="current_password">
                        {(field) => {
                            const error = fieldError(field.state.meta.errors, serverErrors.current_password);

                            return (
                                <FormField htmlFor={field.name} label="Current Password" error={error}>
                                    <PasswordInput
                                        id={field.name}
                                        name={field.name}
                                        value={field.state.value}
                                        onBlur={field.handleBlur}
                                        onChange={(value) => {
                                            clearServerError('current_password');
                                            field.handleChange(value);
                                        }}
                                        autoComplete="current-password"
                                        placeholder="Type current password"
                                        isInvalid={Boolean(error)}
                                    />
                                </FormField>
                            );
                        }}
                    </form.Field>
                </div>

                <div className="col-lg-6">
                    <form.Field name="password">
                        {(field) => {
                            const error = fieldError(field.state.meta.errors, serverErrors.password);

                            return (
                                <FormField htmlFor={field.name} label="New Password" error={error}>
                                    <PasswordInput
                                        id={field.name}
                                        name={field.name}
                                        value={field.state.value}
                                        onBlur={field.handleBlur}
                                        onChange={(value) => {
                                            clearServerError('password');
                                            field.handleChange(value);
                                        }}
                                        autoComplete="new-password"
                                        placeholder="Type new password"
                                        isInvalid={Boolean(error)}
                                    />
                                </FormField>
                            );
                        }}
                    </form.Field>
                </div>

                <div className="col-lg-12">
                    <form.Field name="password_confirmation">
                        {(field) => {
                            const error = fieldError(field.state.meta.errors, serverErrors.password_confirmation);

                            return (
                                <FormField htmlFor={field.name} label="Confirm Password" error={error}>
                                    <PasswordInput
                                        id={field.name}
                                        name={field.name}
                                        value={field.state.value}
                                        onBlur={field.handleBlur}
                                        onChange={(value) => {
                                            clearServerError('password_confirmation');
                                            field.handleChange(value);
                                        }}
                                        autoComplete="new-password"
                                        placeholder="Retype new password"
                                        isInvalid={Boolean(error)}
                                    />
                                </FormField>
                            );
                        }}
                    </form.Field>
                </div>

                <div className="col-lg-12">
                    <div className="form-group d-flex gap-3 align-items-center">
                        <button type="submit" className="btn btn-primary py-2 px-4 fw-medium fs-16" disabled={isSubmitting}>
                            <i className="ri-check-line text-white fw-medium"></i> {isSubmitting ? 'Changing…' : 'Change Password'}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    );
}
