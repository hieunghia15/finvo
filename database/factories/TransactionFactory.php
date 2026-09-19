<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Wallet and category default to the same owner as the transaction, and the
 * category type follows the transaction type, so generated rows always
 * satisfy the business rules.
 *
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'wallet_id' => fn (array $attributes) => Wallet::factory()->state([
                'user_id' => $attributes['user_id'],
            ]),
            'type' => fake()->randomElement(TransactionType::cases()),
            'category_id' => fn (array $attributes) => Category::factory()->state([
                'user_id' => $attributes['user_id'],
                'type' => $attributes['type'],
            ]),
            'amount' => fake()->numberBetween(1, 5_000) * 1000,
            'transaction_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'note' => fake()->optional()->sentence(4),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Income,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Expense,
        ]);
    }
}
