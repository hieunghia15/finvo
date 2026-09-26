import { Category } from '@/types';
import { badgeClass, CATEGORY_STATUS_COLORS, CATEGORY_STATUS_LABELS, CATEGORY_TYPE_COLORS, CATEGORY_TYPE_LABELS } from '@/lib/categoryLabels';

interface CategoryTableProps {
    categories: Category[];
    onEdit: (category: Category) => void;
    onChangeStatus: (category: Category) => void;
    onDelete: (category: Category) => void;
}

interface ActionButtonProps {
    /** Material Symbols icon name. */
    icon: string;
    /** Bootstrap colour of the icon and its tinted background. */
    color: string;
    label: string;
    title: string;
    isDisabled?: boolean;
    onClick: () => void;
}

/**
 * A 34px square icon button with a tinted background, big enough to hit
 * comfortably. Not a `.btn`: Bootstrap sets pointer-events: none on a disabled
 * .btn, which would hide the title explaining why the action is unavailable.
 */
function ActionButton({ icon, color, label, title, isDisabled = false, onClick }: ActionButtonProps) {
    return (
        <button
            type="button"
            className={`wh-34 d-inline-flex align-items-center justify-content-center border-0 rounded-2 bg-${color} bg-opacity-10${isDisabled ? ' opacity-50' : ''}`}
            style={isDisabled ? { cursor: 'not-allowed' } : undefined}
            title={title}
            aria-label={label}
            disabled={isDisabled}
            onClick={onClick}
        >
            <i className={`material-symbols-outlined fs-18 text-${color}`}>{icon}</i>
        </button>
    );
}

/**
 * The categories list, in the order the backend sent it (newest first).
 * The disabled buttons mirror the backend rules for a better UX;
 * the backend still rejects those actions on its own.
 */
export default function CategoryTable({ categories, onEdit, onChangeStatus, onDelete }: CategoryTableProps) {
    return (
        <div className="default-table-area all-projects">
            <div className="table-responsive">
                <table className="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Transactions</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {categories.length === 0 && (
                            <tr>
                                <td colSpan={5} className="text-center text-secondary">
                                    No categories found.
                                </td>
                            </tr>
                        )}

                        {categories.map((category) => {
                            // plan_phase_1.md §4.2: an archived category must be restored before editing.
                            const canEdit = category.status !== 'archived';
                            // §4.4: a category that has transactions cannot be deleted.
                            const canDelete = category.transactions_count === 0;

                            return (
                                <tr key={category.id}>
                                    <td>{category.name}</td>
                                    <td>
                                        <span className={badgeClass(CATEGORY_TYPE_COLORS[category.type])}>{CATEGORY_TYPE_LABELS[category.type]}</span>
                                    </td>
                                    <td>{category.transactions_count}</td>
                                    <td>
                                        <span className={badgeClass(CATEGORY_STATUS_COLORS[category.status])}>{CATEGORY_STATUS_LABELS[category.status]}</span>
                                    </td>
                                    <td>
                                        <div className="d-flex align-items-center gap-2">
                                            <ActionButton
                                                icon="edit"
                                                color="primary"
                                                label={`Edit ${category.name}`}
                                                title={canEdit ? 'Edit' : 'Restore this category before editing.'}
                                                isDisabled={!canEdit}
                                                onClick={() => onEdit(category)}
                                            />
                                            <ActionButton icon="toggle_on" color="warning" label={`Change status of ${category.name}`} title="Change status" onClick={() => onChangeStatus(category)} />
                                            <ActionButton
                                                icon="delete"
                                                color="danger"
                                                label={`Delete ${category.name}`}
                                                title={canDelete ? 'Delete' : 'Categories with transactions cannot be deleted.'}
                                                isDisabled={!canDelete}
                                                onClick={() => onDelete(category)}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
