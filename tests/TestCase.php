<?php

namespace Tests;

use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed reference currencies whenever RefreshDatabase migrates, since
     * registration creates a VND wallet.
     */
    protected bool $seed = true;

    protected string $seeder = CurrencySeeder::class;
}
