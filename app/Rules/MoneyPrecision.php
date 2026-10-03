<?php

namespace App\Rules;

use App\Models\Currency;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Caps the decimal places of an amount at the currency's decimal_places.
 *
 * It counts the digits as written, like Laravel's "decimal" rule: "10.50" is
 * fine for USD, "10.500" is not, and "10.0" is not for VND. The sign and the
 * range are left to "numeric", "min" and "max", so a bad value never gets two
 * errors on the same field.
 */
class MoneyPrecision implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param  Currency  $currency  The currency whose decimal_places caps the value's precision.
     */
    public function __construct(protected Currency $currency) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Not a number, or signed: "numeric" and "min" report it. Bailing
        // out here also keeps an array from reaching preg_match().
        if ((!is_string($value) && !is_int($value) && !is_float($value)) || !is_numeric($value)) {
            return;
        }

        $number = (string) $value;

        // Checked on the string, not with (float) $value < 0: "-0" is not
        // below zero, yet it would fail the pattern below as "not a whole number".
        if (str_starts_with($number, '-')) {
            return;
        }
        $places = $this->currency->decimal_places;

        // Exponents ("1e3", a float such as 1.0E-5) and bare dots ("10.", ".5") are not amounts.
        $isPlainDecimal = preg_match('/^\d+(?:\.(\d+))?$/', $number, $matches) === 1;
        $writtenPlaces = strlen($matches[1] ?? '');

        if ($isPlainDecimal && $writtenPlaces <= $places) {
            return;
        }

        if ($places === 0) {
            $fail(__('The :attribute must be a whole number for :currency.', ['currency' => $this->currency->code]));

            return;
        }

        $fail(__('The :attribute may have at most :places decimal places for :currency.', [
            'places' => $places,
            'currency' => $this->currency->code,
        ]));
    }
}
