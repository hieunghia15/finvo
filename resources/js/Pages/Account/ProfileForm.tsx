import { useState } from 'react';
import { useForm, revalidateLogic } from '@tanstack/react-form';
import { router } from '@inertiajs/react';
import { updateAccountSchema, UpdateAccountFormValues, fieldError } from '@/Schemas';
import { formatDate } from '@/lib/formatDate';
import { IN_PLACE_SUBMIT } from '@/lib/inPlaceSubmit';
import { Account } from '@/types';

type ProfileField = keyof UpdateAccountFormValues;

interface ProfileFormProps {
    account: Account;
}

export default function ProfileForm({ account }: ProfileFormProps) {
    const [serverErrors, setServerErrors] = useState<Partial<Record<ProfileField, string>>>({});

    const form = useForm({
        defaultValues: {
            name: account.name,
        } as UpdateAccountFormValues,
        validationLogic: revalidateLogic(),
        validators: {
            onDynamic: updateAccountSchema,
        },
        onSubmit: ({ value, formApi }) => {
            setServerErrors({});
            return new Promise<void>((resolve) => {
                router.patch('/account', value, {
                    ...IN_PLACE_SUBMIT,
                    // Show the saved, server-normalized name; it also becomes
                    // the value Cancel returns to.
                    onSuccess: (page) => formApi.reset({ name: (page.props.account as Account).name }),
                    onError: (errors) => setServerErrors(errors),
                    onFinish: () => resolve(),
                });
            });
        },
    });

    const clearServerError = (field: ProfileField) => setServerErrors((prev) => ({ ...prev, [field]: undefined }));

    return (
        <>
            <div className="mb-4">
                <h4 className="fs-20 mb-1">Profile</h4>
                <p className="fs-15">Update your personal details here.</p>
            </div>

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    form.handleSubmit();
                }}
            >
                <div className="row">
                    <form.Field name="name">
                        {(field) => {
                            const error = fieldError(field.state.meta.errors, serverErrors.name);

                            return (
                                <div className="col-lg-6">
                                    <div className="form-group mb-4">
                                        <label htmlFor={field.name} className="label text-secondary">
                                            Full Name
                                        </label>
                                        <div className="form-group position-relative">
                                            <input
                                                id={field.name}
                                                name={field.name}
                                                type="text"
                                                className={`form-control text-dark ps-5 h-55${error ? ' is-invalid' : ''}`}
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
                                            <i className="ri-user-line position-absolute top-50 start-0 translate-middle-y fs-20 text-gray-light ps-20"></i>
                                        </div>
                                        {error && <div className="invalid-feedback d-block">{error}</div>}
                                    </div>
                                </div>
                            );
                        }}
                    </form.Field>

                    <div className="col-lg-6">
                        <div className="form-group mb-4">
                            <label htmlFor="email" className="label text-secondary">
                                Email Address
                            </label>
                            <div className="form-group position-relative">
                                <input id="email" type="email" className="form-control text-dark ps-5 h-55" value={account.email} disabled />
                                <i className="ri-mail-line position-absolute top-50 start-0 translate-middle-y fs-20 text-gray-light ps-20"></i>
                            </div>
                            <span className="d-block fs-14 text-secondary mt-1">Email cannot be changed.</span>
                        </div>
                    </div>

                    <div className="col-lg-6">
                        <div className="form-group mb-4">
                            <label htmlFor="registered_on" className="label text-secondary">
                                Registered On
                            </label>
                            <div className="form-group position-relative">
                                <input id="registered_on" type="text" className="form-control text-dark ps-5 h-55" value={formatDate(account.created_at)} disabled />
                                <i className="ri-calendar-line position-absolute top-50 start-0 translate-middle-y fs-20 text-gray-light ps-20"></i>
                            </div>
                        </div>
                    </div>

                    <div className="col-lg-12">
                        <form.Subscribe selector={(state) => state.isSubmitting}>
                            {(isSubmitting) => (
                                <div className="d-flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        className="btn btn-danger py-2 px-4 fw-medium fs-16 text-white"
                                        disabled={isSubmitting}
                                        onClick={() => {
                                            form.reset();
                                            setServerErrors({});
                                        }}
                                    >
                                        Cancel
                                    </button>
                                    <button type="submit" className="btn btn-primary py-2 px-4 fw-medium fs-16" disabled={isSubmitting}>
                                        <i className="ri-check-line text-white fw-medium"></i> {isSubmitting ? 'Saving…' : 'Save Changes'}
                                    </button>
                                </div>
                            )}
                        </form.Subscribe>
                    </div>
                </div>
            </form>
        </>
    );
}
