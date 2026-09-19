<?php

namespace App\Enums\Concerns;

/**
 * Shared helpers for string-backed enums exposed to the frontend.
 *
 * `@property-read` is for IDEs (Intelephense), which cannot infer the
 * backing value inside a trait; PHPStan gets it from the require tag.
 *
 * @phpstan-require-implements \BackedEnum
 *
 * @property-read string $value
 */
trait HasOptions
{
    /**
     * Human-readable, translatable label for the case.
     *
     * @return string Label in the current locale.
     */
    abstract public function label(): string;

    /**
     * All raw values in declaration order, e.g. for `Rule::in()` or TypeScript unions.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Value/label pairs in declaration order for select inputs passed as Inertia props.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * Translate a label key, narrowing `__()`'s `array|string` result to a string.
     *
     * @param  non-empty-string  $key  Translation key; also the fallback label.
     *
     * @return string The translated label, or `$key` if it resolves to a translation group.
     */
    protected static function translate(string $key): string
    {
        $label = __($key);

        return is_string($label) ? $label : $key;
    }
}
