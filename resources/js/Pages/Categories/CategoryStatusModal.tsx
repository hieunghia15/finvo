import { useId, useState } from 'react';
import { useForm, useStore, revalidateLogic } from '@tanstack/react-form';
import { router } from '@inertiajs/react';
import Modal from '@/Components/Common/Modal';
import { updateCategoryStatusSchema, UpdateCategoryStatusFormValues } from '@/Schemas';
import { fieldError } from '@/lib/fieldError';
import { IN_PLACE_SUBMIT } from '@/lib/inPlaceSubmit';
import { CATEGORY_STATUS_DESCRIPTIONS, CATEGORY_STATUS_LABELS } from '@/lib/categoryLabels';
import { Category, CategoryStatus } from '@/types';

interface CategoryStatusModalProps {
    category: Category;
    onClose: () => void;
}

const STATUS_OPTIONS = Object.entries(CATEGORY_STATUS_LABELS) as [CategoryStatus, string][];

/**
 * Move a category to another status. Every transition is allowed, including
 * restoring an archived category, so this is also the way back from archived.
 */
export default function CategoryStatusModal({ category, onClose }: CategoryStatusModalProps) {
    const formId = useId();
    const [statusServerError, setStatusServerError] = useState<string>();

    const form = useForm({
        defaultValues: { status: category.status } as UpdateCategoryStatusFormValues,
        validationLogic: revalidateLogic(),
        validators: { onDynamic: updateCategoryStatusSchema },
        onSubmit: ({ value }) => {
            setStatusServerError(undefined);

            // router.patch has no promise to await, so wrap it in one and resolve
            // on onFinish; otherwise isSubmitting would flip back immediately.
            return new Promise<void>((resolve) => {
                router.patch(`/categories/${category.id}/status`, value, {
                    ...IN_PLACE_SUBMIT,
                    onSuccess: () => onClose(),
                    onError: (errors) => setStatusServerError(errors.status),
                    onFinish: () => resolve(),
                });
            });
        },
    });

    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);
    const selectedStatus = useStore(form.store, (state) => state.values.status);
    const isUnchanged = selectedStatus === category.status;

    return (
        <Modal
            title="Change Status"
            onClose={onClose}
            isCloseDisabled={isSubmitting}
            footer={
                <>
                    <button type="button" className="btn btn-danger text-white" disabled={isSubmitting} onClick={onClose}>
                        Cancel
                    </button>
                    {/* The footer sits outside the <form>, so the button points at it by id. */}
                    <button type="submit" form={formId} className="btn btn-primary text-white" disabled={isSubmitting || isUnchanged}>
                        {isSubmitting ? 'Saving…' : 'Save'}
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
                <p className="mb-4">
                    Category: <strong>{category.name}</strong>
                </p>

                <form.Field name="status">
                    {(field) => {
                        const error = fieldError(field.state.meta.errors, statusServerError);
                        const inputId = `${formId}-status`;

                        return (
                            <div className="form-group mb-2">
                                <label htmlFor={inputId} className="label text-secondary">
                                    Status
                                </label>
                                <div className="form-group">
                                    <select
                                        id={inputId}
                                        name={field.name}
                                        className={`form-select form-control text-dark h-55${error ? ' is-invalid' : ''}`}
                                        value={field.state.value}
                                        onBlur={field.handleBlur}
                                        onChange={(e) => {
                                            setStatusServerError(undefined);
                                            field.handleChange(e.target.value as CategoryStatus);
                                        }}
                                        autoFocus
                                    >
                                        {STATUS_OPTIONS.map(([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <span className="d-block fs-14 text-secondary mt-1">{CATEGORY_STATUS_DESCRIPTIONS[field.state.value]}</span>
                                {error && <div className="invalid-feedback d-block">{error}</div>}
                            </div>
                        );
                    }}
                </form.Field>
            </form>
        </Modal>
    );
}
