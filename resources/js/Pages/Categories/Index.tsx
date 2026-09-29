import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import Breadcrumb from '@/Components/Layout/Breadcrumb';
import FlashToasts from '@/Components/Common/FlashToasts';
import { IN_PLACE_FILTER } from '@/lib/inPlaceSubmit';
import { CATEGORY_TYPE_LABELS } from '@/lib/categoryLabels';
import { Category, CategoryFilters, CategoryType, PageProps } from '@/types';
import CategoryTable from './CategoryTable';
import CategoryFormModal from './CategoryFormModal';
import CategoryStatusModal from './CategoryStatusModal';
import DeleteCategoryModal from './DeleteCategoryModal';
import { useTranslation } from '@/hooks/useTranslation';
import { trans } from '@/lib/i18n';

type IndexProps = PageProps<{ categories: Category[]; filters: CategoryFilters }>;

type ModalState = { kind: 'none' } | { kind: 'create' } | { kind: 'edit'; category: Category } | { kind: 'status'; category: Category } | { kind: 'delete'; category: Category };

const TYPE_TABS: { type: CategoryType | null; label: string }[] = [
    { type: null, label: trans('All') },
    { type: 'income', label: CATEGORY_TYPE_LABELS.income },
    { type: 'expense', label: CATEGORY_TYPE_LABELS.expense },
];

function tabButtonClass(isActive: boolean): string {
    const colors = isActive ? 'bg-primary text-white' : 'bg-transparent text-primary';

    return `btn btn-primary border border-primary py-2 px-3 fw-semibold ${colors}`;
}

/** Only non-default filters go into the URL, so the plain list stays at /categories. */
function toQuery(filters: CategoryFilters): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.type) {
        query.type = filters.type;
    }
    if (filters.include_archived) {
        query.include_archived = '1';
    }

    return query;
}

export default function Index({ categories, filters }: IndexProps) {
    const { t } = useTranslation();
    const [modal, setModal] = useState<ModalState>({ kind: 'none' });

    const closeModal = () => setModal({ kind: 'none' });

    // The controls read the echoed `filters` prop instead of keeping their own
    // state, so they always match the URL (reload, back/forward, bookmarks).
    const applyFilters = (next: CategoryFilters) => {
        router.get('/categories', toQuery(next), IN_PLACE_FILTER);
    };

    return (
        <MainLayout>
            <Head title={t('Categories')} />

            <Breadcrumb
                title={t('Categories')}
                items={[
                    { label: t('Dashboard'), url: '/dashboard' },
                    { label: t('Categories'), active: true },
                ]}
            />

            <FlashToasts />

            <div className="card bg-white border-0 rounded-3 mb-4">
                <div className="card-body p-4">
                    <div className="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 mb-lg-4">
                        <div className="d-flex align-items-center flex-wrap gap-2 gap-lg-3">
                            <ul className="ps-0 mb-0 list-unstyled d-flex flex-wrap gap-2">
                                {TYPE_TABS.map((tab) => {
                                    const isActive = filters.type === tab.type;

                                    return (
                                        <li key={tab.label}>
                                            <button
                                                type="button"
                                                className={tabButtonClass(isActive)}
                                                aria-current={isActive ? 'page' : undefined}
                                                onClick={() => applyFilters({ ...filters, type: tab.type })}
                                            >
                                                {t(tab.label)}
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>

                            <div className="form-check mb-0">
                                <input
                                    id="show-archived"
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={filters.include_archived}
                                    onChange={(e) => applyFilters({ ...filters, include_archived: e.target.checked })}
                                />
                                <label htmlFor="show-archived" className="form-check-label">
                                    {t('Show archived')}
                                </label>
                            </div>
                        </div>

                        <button type="button" className="btn btn-outline-primary py-1 px-2 px-sm-4 fs-14 fw-medium rounded-3 hover-bg" onClick={() => setModal({ kind: 'create' })}>
                            <span className="py-sm-1 d-block">
                                <i className="ri-add-line d-none d-sm-inline-block"></i> <span>{t('Add New Category')}</span>
                            </span>
                        </button>
                    </div>

                    <CategoryTable
                        categories={categories}
                        onEdit={(category) => setModal({ kind: 'edit', category })}
                        onChangeStatus={(category) => setModal({ kind: 'status', category })}
                        onDelete={(category) => setModal({ kind: 'delete', category })}
                    />
                </div>
            </div>

            {/* Mounted only while open, so each opening starts with a clean form. */}
            {modal.kind === 'create' && <CategoryFormModal defaultType={filters.type ?? 'expense'} onClose={closeModal} />}
            {modal.kind === 'edit' && <CategoryFormModal category={modal.category} defaultType={modal.category.type} onClose={closeModal} />}
            {modal.kind === 'status' && <CategoryStatusModal category={modal.category} onClose={closeModal} />}
            {modal.kind === 'delete' && <DeleteCategoryModal category={modal.category} onClose={closeModal} />}
        </MainLayout>
    );
}
