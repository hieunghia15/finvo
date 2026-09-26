import { useId, useState } from 'react';
import { useForm, useStore, revalidateLogic } from '@tanstack/react-form';
import { router } from '@inertiajs/react';
import Modal from '@/Components/Common/Modal';
import { categoryFormSchema, CategoryFormValues } from '@/Schemas';
import { fieldError } from '@/lib/fieldError';
import { IN_PLACE_SUBMIT } from '@/lib/inPlaceSubmit';
import { CATEGORY_TYPE_LABELS } from '@/lib/categoryLabels';
import { Category, CategoryType } from '@/types';

/**
 * Validation errors Laravel sends back. `status` is set when the category was
 * archived in the meantime, and belongs to no field of this form.
 */
type CategoryServerErrors = {
    name?: string;
    type?: string;
    status?: string;
};

interface CategoryFormModalProps {
    /** The category being edited; omitted when creating. */
    category?: Category;
    /** Pre-selected type when creating. */
    defaultType: CategoryType;
    onClose: () => void;
}

const TYPE_OPTIONS = Object.entries(CATEGORY_TYPE_LABELS) as [CategoryType, string][];

/**
 * Add and edit a category's name and type. The status has its own modal,
 * because the backend changes it through a separate endpoint.
 */
export default function CategoryFormModal({ category, defaultType, onClose }: CategoryFormModalProps) {
    const formId = useId();
    const [serverErrors, setServerErrors] = useState<CategoryServerErrors>({});

    const isEditing = category !== undefined;
    // plan_phase_1.md §4.4: the type is locked once the category has been used.
    const isTypeLocked = isEditing && category.transactions_count > 0;

    const form = useForm({
        defaultValues: {
            name: category?.name ?? '',
            type: category?.type ?? defaultType,
        } as CategoryFormValues,
        validationLogic: revalidateLogic(),
        validators: { onDynamic: categoryFormSchema },
        onSubmit: ({ value }) => {
            setServerErrors({});

            // router.post/patch have no promise to await, so wrap them in one and
            // resolve on onFinish; otherwise isSubmitting would flip back immediately.
            return new Promise<void>((resolve) => {
                const options = {
                    ...IN_PLACE_SUBMIT,
                    onSuccess: () => onClose(),
                    onError: (errors: CategoryServerErrors) => setServerErrors(errors),
                    onFinish: () => resolve(),
                };

                if (isEditing) {
                    router.patch(`/categories/${category.id}`, value, options);
                } else {
                    router.post('/categories', value, options);
                }
            });
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    const clearServerError = (field: keyof CategoryServerErrors) => {
        setServerErrors((previous) => ({ ...previous, [field]: undefined }));
    };

    const submitLabel = isEditing ? (isSubmitting ? 'Saving…' : 'Save Changes') : isSubmitting ? 'Creating…' : 'Create';

    return (
        <Modal
            title={isEditing ? 'Edit Category' : 'Add New Category'}
            onClose={onClose}
            isCloseDisabled={isSubmitting}
            footer={
                <>
                    <button type="button" className="btn btn-danger text-white" disabled={isSubmitting} onClick={onClose}>
                        Cancel
                    </button>
                    {/* The footer sits outside the <form>, so the button points at it by id. */}
                    <button type="submit" form={formId} className="btn btn-primary text-white" disabled={isSubmitting}>
                        {submitLabel}
                    </button>
                </>
            }
        >
            <form
                id={formId}
                onSubmit={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    form.handleSubmit();
                }}
            >
                {serverErrors.status && (
                    <div className="alert alert-danger" role="alert">
                        {serverErrors.status}
                    </div>
                )}

                <form.Field name="name">
                    {(field) => {
                        const error = fieldError(field.state.meta.errors, serverErrors.name);
                        const inputId = `${formId}-name`;

                        return (
                            <div className="form-group mb-4">
                                <label htmlFor={inputId} className="label text-secondary">
                                    Name
                                </label>
                                <div className="form-group">
                                    <input
                                        id={inputId}
                                        name={field.name}
                                        type="text"
                                        className={`form-control text-dark h-55${error ? ' is-invalid' : ''}`}
                                        placeholder="Enter category name"
                                        maxLength={100}
                                        value={field.state.value}
                                        onBlur={field.handleBlur}
                                        onChange={(e) => {
                                            clearServerError('name');
                                            field.handleChange(e.target.value);
                                        }}
                                        autoComplete="off"
                                        autoFocus
                                    />
                                </div>
                                {error && <div className="invalid-feedback d-block">{error}</div>}
                            </div>
                        );
                    }}
                </form.Field>

                <form.Field name="type">
                    {(field) => {
                        const error = fieldError(field.state.meta.errors, serverErrors.type);
                        const inputId = `${formId}-type`;

                        return (
                            <div className="form-group mb-2">
                                <label htmlFor={inputId} className="label text-secondary">
                                    Type
                                </label>
                                <div className="form-group">
                                    <select
                                        id={inputId}
                                        name={field.name}
                                        className={`form-select form-control text-dark h-55${error ? ' is-invalid' : ''}`}
                                        value={field.state.value}
                                        onBlur={field.handleBlur}
                                        onChange={(e) => {
                                            clearServerError('type');
                                            field.handleChange(e.target.value as CategoryType);
                                        }}
                                        // Still submitted from the form state: the backend requires the type.
                                        disabled={isTypeLocked}
                                    >
                                        {TYPE_OPTIONS.map(([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                {isTypeLocked && <span className="d-block fs-14 text-secondary mt-1">Type is locked because this category has transactions.</span>}
                                {error && <div className="invalid-feedback d-block">{error}</div>}
                            </div>
                        );
                    }}
                </form.Field>
            </form>
        </Modal>
    );
}
