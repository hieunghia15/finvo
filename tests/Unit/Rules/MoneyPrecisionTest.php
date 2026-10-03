<?php

namespace Tests\Unit\Rules;

use App\Models\Currency;
use App\Rules\MoneyPrecision;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MoneyPrecisionTest extends TestCase
{
    /**
     * Validate a value against the rule and return its error messages.
     *
     * @param  mixed  $value  The value to validate.
     * @param  int  $decimalPlaces  The currency's decimal_places.
     * @param  string  $code  The currency code.
     *
     * @return array<int, string> The error messages; empty when the rule passes.
     */
    protected function errorsFor(mixed $value, int $decimalPlaces, string $code): array
    {
        $currency = new Currency(['code' => $code, 'decimal_places' => $decimalPlaces]);

        return Validator::make(
            ['amount' => $value],
            ['amount' => [new MoneyPrecision($currency)]],
        )->errors()->all();
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function validVndAmounts(): array
    {
        return [
            'integer string' => ['100'],
            'zero' => ['0'],
            'int' => [100],
        ];
    }

    #[DataProvider('validVndAmounts')]
    public function test_a_whole_number_passes_for_a_zero_decimal_currency(mixed $value): void
    {
        $this->assertSame([], $this->errorsFor($value, 0, 'VND'));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidVndAmounts(): array
    {
        return [
            'one decimal' => ['100.5'],
            'written zero decimal' => ['100.0'],
            'float' => [100.5],
        ];
    }

    #[DataProvider('invalidVndAmounts')]
    public function test_decimals_fail_for_a_zero_decimal_currency(mixed $value): void
    {
        $this->assertSame(
            ['The amount must be a whole number for VND.'],
            $this->errorsFor($value, 0, 'VND'),
        );
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function validUsdAmounts(): array
    {
        return [
            'integer' => ['10'],
            'one decimal' => ['10.5'],
            'two decimals' => ['10.50'],
            'float' => [10.5],
        ];
    }

    #[DataProvider('validUsdAmounts')]
    public function test_up_to_two_decimals_pass_for_usd(mixed $value): void
    {
        $this->assertSame([], $this->errorsFor($value, 2, 'USD'));
    }

    public function test_a_third_decimal_fails_for_usd(): void
    {
        $this->assertSame(
            ['The amount may have at most 2 decimal places for USD.'],
            $this->errorsFor('10.505', 2, 'USD'),
        );
    }

    public function test_decimals_are_counted_as_written(): void
    {
        $this->assertNotSame([], $this->errorsFor('10.500', 2, 'USD'));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function notPlainDecimals(): array
    {
        return [
            'exponent' => ['1e3'],
            'trailing dot' => ['10.'],
            'leading dot' => ['.5'],
            'float in exponent form' => [0.00001],
        ];
    }

    #[DataProvider('notPlainDecimals')]
    public function test_numbers_that_are_not_plain_decimals_fail(mixed $value): void
    {
        $this->assertNotSame([], $this->errorsFor($value, 2, 'USD'));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function valuesLeftToOtherRules(): array
    {
        return [
            'negative' => ['-5'],
            'not numeric' => ['abc'],
            'array' => [['1']],
            'null' => [null],
            'boolean' => [true],
        ];
    }

    #[DataProvider('valuesLeftToOtherRules')]
    public function test_it_stays_silent_on_values_other_rules_report(mixed $value): void
    {
        $this->assertSame([], $this->errorsFor($value, 0, 'VND'));
    }
}
