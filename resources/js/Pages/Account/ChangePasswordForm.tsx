import { useState } from 'react';
import { useForm, revalidateLogic } from '@tanstack/react-form';
import { router } from '@inertiajs/react';
import { updatePasswordSchema, UpdatePasswordFormValues, fieldError } from '@/Schemas';
import PasswordInput, { PasswordInputProps } from '@/Components/Form/PasswordInput';

type PasswordField = keyof UpdatePasswordFormValues;

const PASSWORD_FIELDS: {
    name: PasswordField;
    label: string;
    placeholder: string;
    autoComplete: PasswordInputProps['autoComplete'];
    columnClass: string;
}[] = [
    { name: 'current_password', label: 'Current Password', placeholder: 'Type current password', autoComplete: 'current-password', columnClass: 'col-lg-6' },
    { name: 'password', label: 'New Password', placeholder: 'Type new password', autoComplete: 'new-password', columnClass: 'col-lg-6' },
    { name: 'password_confirmation', label: 'Confirm Password', placeholder: 'Retype new password', autoComplete: 'new-password', columnClass: 'col-lg-12' },
];

export default function ChangePasswordForm() {
    const [serverErrors, setServerErrors] = useState<Partial<Record<PasswordField, string>>>({});

    const form = useForm({
        defaultValues: {
            current_password: '',
            password: '',
            password_confirmation: '',
        } as UpdatePasswordFormValues,
        validationLogic: revalidateLogic(),
        validators: {
            onDynamic: updatePasswordSchema,
        },
        onSubmit: ({ value, formApi }) => {
            setServerErrors({});
            return new Promise<void>((resolve) => {
                // preserveState keeps this tab open after the redirect back to
                // /account; the fields are cleared once the password is changed.
                router.put('/account/password', value, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => formApi.reset(),
                    onError: (errors) => setServerErrors(errors),
                    onFinish: () => resolve(),
                });
            });
        },
    });

    const clearServerError = (field: PasswordField) => setServerErrors((prev) => ({ ...prev, [field]: undefined }));

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();
                form.handleSubmit();
            }}
        >
            <div className="row">
                {PASSWORD_FIELDS.map(({ name, label, placeholder, autoComplete, columnClass }) => (
                    <form.Field key={name} name={name}>
                        {(field) => {
                            const error = fieldError(field.state.meta.errors, serverErrors[name]);

                            return (
                                <div className={columnClass}>
                                    <div className="form-group mb-4">
                                        <label htmlFor={field.name} className="label text-secondary">
                                            {label}
                                        </label>
                                        <PasswordInput
                                            id={field.name}
                                            name={field.name}
                                            value={field.state.value}
                                            onBlur={field.handleBlur}
                                            onChange={(value) => {
                                                clearServerError(name);
                                                field.handleChange(value);
                                            }}
                                            autoComplete={autoComplete}
                                            placeholder={placeholder}
                                            isInvalid={Boolean(error)}
                                        />
                                        {error && <div className="invalid-feedback d-block">{error}</div>}
                                    </div>
                                </div>
                            );
                        }}
                    </form.Field>
                ))}

                <div className="col-lg-12">
                    <form.Subscribe selector={(state) => state.isSubmitting}>
                        {(isSubmitting) => (
                            <div className="form-group d-flex gap-3 align-items-center">
                                <button type="submit" className="btn btn-primary py-2 px-4 fw-medium fs-16" disabled={isSubmitting}>
                                    <i className="ri-check-line text-white fw-medium"></i> {isSubmitting ? 'Changing…' : 'Change Password'}
                                </button>
                            </div>
                        )}
                    </form.Subscribe>
                </div>
            </div>
        </form>
    );
}
