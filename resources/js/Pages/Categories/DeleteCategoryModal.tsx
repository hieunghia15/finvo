import { useState } from 'react';
import { router } from '@inertiajs/react';
import Modal from '@/Components/Common/Modal';
import { IN_PLACE_SUBMIT } from '@/lib/inPlaceSubmit';
import { Category } from '@/types';
import { useTranslation } from '@/hooks/useTranslation';

interface DeleteCategoryModalProps {
    category: Category;
    onClose: () => void;
}

/**
 * Confirm before deleting a category. The result, deleted or still in use,
 * comes back as a flash message on the page, not inside this modal.
 */
export default function DeleteCategoryModal({ category, onClose }: DeleteCategoryModalProps) {
    const { t } = useTranslation();
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDelete = () => {
        setIsDeleting(true);

        router.delete(`/categories/${category.id}`, {
            ...IN_PLACE_SUBMIT,
            // Not onSuccess: a category still in use also comes back as a
            // successful redirect, just with flash.error set.
            onFinish: () => onClose(),
        });
    };

    return (
        <Modal
            title={t('Delete Category')}
            size="sm"
            onClose={onClose}
            isCloseDisabled={isDeleting}
            footer={
                <>
                    <button type="button" className="btn btn-primary text-white" disabled={isDeleting} onClick={onClose}>
                        {t('Cancel')}
                    </button>
                    <button type="button" className="btn btn-danger text-white" disabled={isDeleting} onClick={handleDelete}>
                        {isDeleting ? t('Deleting…') : t('Delete')}
                    </button>
                </>
            }
        >
            <p className="mb-0">{t('Delete ":name"? This cannot be undone.', { name: category.name })}</p>
        </Modal>
    );
}
