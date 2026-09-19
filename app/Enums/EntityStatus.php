<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Lifecycle status shared by wallets and categories.
 *
 * The predicates encode the status matrix in plan_phase_1.md §4.2. Every
 * status still counts towards balances and statistics, and transitions
 * between statuses are unrestricted.
 */
enum EntityStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    /**
     * {@inheritDoc}
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => self::translate('Active'),
            self::Inactive => self::translate('Inactive'),
            self::Archived => self::translate('Archived'),
        };
    }

    /**
     * Whether a new transaction can use it, or an edited transaction can be moved onto it.
     *
     * An existing transaction may always keep its current wallet/category.
     *
     * @return bool True only for {@see self::Active}.
     */
    public function isSelectable(): bool
    {
        return $this === self::Active;
    }

    /**
     * Whether its details (name, description, ...) can be edited.
     *
     * @return bool False for {@see self::Archived}; it must be restored first.
     */
    public function isEditable(): bool
    {
        return $this !== self::Archived;
    }

    /**
     * Whether it appears in lists without the "archived" filter turned on.
     *
     * @return bool False for {@see self::Archived}.
     */
    public function isVisibleByDefault(): bool
    {
        return $this !== self::Archived;
    }
}
