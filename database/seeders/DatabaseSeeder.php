<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Required everywhere, production included: registration needs VND.
        $this->call(CurrencySeeder::class);

        // The admin password is hardcoded, so production never gets this
        // account; users there sign up through the registration page.
        if (!app()->isProduction()) {
            $this->call(AdminUserSeeder::class);
        }

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
