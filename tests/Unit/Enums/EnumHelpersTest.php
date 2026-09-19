<?php

namespace Tests\Unit\Enums;

use App\Enums\EntityStatus;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use Tests\TestCase;

class EnumHelpersTest extends TestCase
{
    public function test_values_returns_raw_values_in_declaration_order(): void
    {
        $this->assertSame(['income', 'expense'], TransactionType::values());
        $this->assertSame(['active', 'inactive', 'archived'], EntityStatus::values());
        $this->assertSame(['cash', 'bank', 'e_wallet', 'credit_card', 'other'], WalletType::values());
    }

    public function test_options_pairs_each_value_with_its_label(): void
    {
        $this->assertSame([
            ['value' => 'income', 'label' => 'Income'],
            ['value' => 'expense', 'label' => 'Expense'],
        ], TransactionType::options());

        $this->assertCount(count(WalletType::cases()), WalletType::options());
    }

    public function test_transaction_type_predicates(): void
    {
        $this->assertTrue(TransactionType::Income->isIncome());
        $this->assertFalse(TransactionType::Income->isExpense());
        $this->assertTrue(TransactionType::Expense->isExpense());
        $this->assertFalse(TransactionType::Expense->isIncome());
    }

    public function test_entity_status_follows_the_status_matrix(): void
    {
        $this->assertTrue(EntityStatus::Active->isSelectable());
        $this->assertFalse(EntityStatus::Inactive->isSelectable());
        $this->assertFalse(EntityStatus::Archived->isSelectable());

        $this->assertTrue(EntityStatus::Active->isEditable());
        $this->assertTrue(EntityStatus::Inactive->isEditable());
        $this->assertFalse(EntityStatus::Archived->isEditable());

        $this->assertTrue(EntityStatus::Active->isVisibleByDefault());
        $this->assertTrue(EntityStatus::Inactive->isVisibleByDefault());
        $this->assertFalse(EntityStatus::Archived->isVisibleByDefault());
    }
}
