<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Real ISO 4217 codes are letters only, so digits never collide with CurrencySeeder rows.
            'code' => fake()->unique()->numerify('9##'),
            'name' => fake()->words(2, true),
            'symbol' => fake()->randomElement(['$', '€', '£', '¥']),
            'decimal_places' => 2,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the currency can no longer be picked for new wallets.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
