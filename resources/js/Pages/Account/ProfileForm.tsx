import { useState } from 'react';
import { useForm, useStore, revalidateLogic } from '@tanstack/react-form';
import { router } from '@inertiajs/react';
import FormField from '@/Components/Form/FormField';
import { updateAccountSchema } from '@/Schemas';
import { fieldError } from '@/lib/fieldError';
import { formatDate } from '@/lib/formatDate';
import { IN_PLACE_SUBMIT } from '@/lib/inPlaceSubmit';
import { Account } from '@/types';

interface ProfileFormProps {
    account: Account;
}

/**
 * Profile tab: the name can be edited, email and registration date are read-only.
 */
export default function ProfileForm({ account }: ProfileFormProps) {
    // Validation error Laravel sent back for the name field, if any.
    const [nameServerError, setNameServerError] = useState<string>();

    const form = useForm({
        defaultValues: { name: account.name },
        validationLogic: revalidateLogic(),
        validators: { onDynamic: updateAccountSchema },
        onSubmit: ({ value, formApi }) => {
            setNameServerError(undefined);

            // router.patch has no promise to await, so wrap it in one and resolve
            // on onFinish; otherwise isSubmitting would flip back immediately.
            return new Promise<void>((resolve) => {
                router.patch('/account', value, {
                    ...IN_PLACE_SUBMIT,
                    // Load the saved, server-normalized name back into the form.
                    // It also becomes the value Cancel returns to.
                    onSuccess: (page) => formApi.reset({ name: (page.props.account as Account).name }),
                    onError: (errors) => setNameServerError(errors.name),
                    onFinish: () => resolve(),
                });
            });
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    const handleCancel = () => {
        form.reset();
        setNameServerError(undefined);
    };

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
                    <div className="col-lg-6">
                        <form.Field name="name">
                            {(field) => {
                                const error = fieldError(field.state.meta.errors, nameServerError);

                                return (
                                    <FormField htmlFor={field.name} label="Full Name" error={error}>
                                        <div className="form-group position-relative">
                                            <input
                                                id={field.name}
                                                name={field.name}
                                                type="text"
                                                className={`form-control text-dark ps-5 h-55${error ? ' is-invalid' : ''}`}
                                                // Bootstrap's invalid icon would sit on top of the field icon.
                                                style={error ? { backgroundImage: 'none' } : undefined}
                                                placeholder="Enter your full name"
                                                value={field.state.value}
                                                onBlur={field.handleBlur}
                                                onChange={(e) => {
                                                    setNameServerError(undefined);
                                                    field.handleChange(e.target.value);
                                                }}
                                                autoComplete="name"
                                            />
                                            <i className="ri-user-line position-absolute top-50 start-0 translate-middle-y fs-20 text-gray-light ps-20"></i>
                                        </div>
                                    </FormField>
                                );
                            }}
                        </form.Field>
                    </div>

                    <div className="col-lg-6">
                        <FormField htmlFor="email" label="Email Address" hint="Email cannot be changed.">
                            <div className="form-group position-relative">
                                <input id="email" type="email" className="form-control text-dark ps-5 h-55" value={account.email} disabled />
                                <i className="ri-mail-line position-absolute top-50 start-0 translate-middle-y fs-20 text-gray-light ps-20"></i>
                            </div>
                        </FormField>
                    </div>

                    <div className="col-lg-6">
                        <FormField htmlFor="registered_on" label="Registered On">
                            <div className="form-group position-relative">
                                <input id="registered_on" type="text" className="form-control text-dark ps-5 h-55" value={formatDate(account.created_at)} disabled />
                                <i className="ri-calendar-line position-absolute top-50 start-0 translate-middle-y fs-20 text-gray-light ps-20"></i>
                            </div>
                        </FormField>
                    </div>

                    <div className="col-lg-12">
                        <div className="d-flex flex-wrap gap-3">
                            <button type="button" className="btn btn-danger py-2 px-4 fw-medium fs-16 text-white" disabled={isSubmitting} onClick={handleCancel}>
                                Cancel
                            </button>
                            <button type="submit" className="btn btn-primary py-2 px-4 fw-medium fs-16" disabled={isSubmitting}>
                                <i className="ri-check-line text-white fw-medium"></i> {isSubmitting ? 'Saving…' : 'Save Changes'}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </>
    );
}
