<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

/**
 * Reference data required in every environment, including production:
 * registration creates a VND wallet.
 */
class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $currencies = [
            ['code' => 'VND', 'name' => 'Việt Nam Đồng', 'symbol' => '₫', 'decimal_places' => 0],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_places' => 0],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'decimal_places' => 2],
            ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩', 'decimal_places' => 0],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimal_places' => 2],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿', 'decimal_places' => 2],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'decimal_places' => 2],
        ];

        // is_active is only set on insert so a manually deactivated
        // currency stays deactivated when the seeder is re-run.
        Currency::upsert(
            array_map(fn (array $currency) => $currency + [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], $currencies),
            ['code'],
            ['name', 'symbol', 'decimal_places', 'updated_at'],
        );
    }
}
