<?php

namespace Database\Factories;

use App\Enums\EntityStatus;
use App\Enums\WalletType;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
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
            'name' => fake()->unique()->words(2, true),
            'type' => fake()->randomElement(WalletType::cases()),
            'currency_code' => Currency::factory(),
            'initial_balance' => fake()->numberBetween(0, 10_000) * 1000,
            'description' => fake()->optional()->sentence(),
            'status' => EntityStatus::Active,
        ];
    }

    /**
     * Use an existing currency, e.g. one inserted by CurrencySeeder.
     */
    public function currency(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'currency_code' => $code,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EntityStatus::Inactive,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EntityStatus::Archived,
        ]);
    }
}
