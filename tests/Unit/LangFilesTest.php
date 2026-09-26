<?php

namespace Tests\Unit;

use App\Services\UserOnboardingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LangFilesTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function viDictionary(): array
    {
        return json_decode((string) file_get_contents(lang_path('vi.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_vi_json_has_sorted_non_empty_string_translations(): void
    {
        $dictionary = $this->viDictionary();

        $this->assertNotEmpty($dictionary);

        foreach ($dictionary as $key => $value) {
            $this->assertIsString($value, "Translation of \"{$key}\" is not a string.");
            $this->assertNotSame('', trim($value), "Translation of \"{$key}\" is empty.");
        }

        $keys = array_keys($dictionary);
        $sorted = $keys;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $keys, 'lang/vi.json keys must be sorted alphabetically.');
    }

    public function test_vi_json_translates_every_default_onboarding_name(): void
    {
        $dictionary = $this->viDictionary();
        $keys = [UserOnboardingService::DEFAULT_WALLET_NAME, ...array_merge(...array_values(UserOnboardingService::DEFAULT_CATEGORIES))];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $dictionary, "Default onboarding name \"{$key}\" has no Vietnamese translation.");
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function groupFiles(): array
    {
        return [
            'auth' => ['auth'],
            'pagination' => ['pagination'],
            'passwords' => ['passwords'],
            'validation' => ['validation'],
        ];
    }

    #[DataProvider('groupFiles')]
    public function test_vi_group_file_has_every_key_of_the_english_one(string $group): void
    {
        $english = require lang_path("en/{$group}.php");
        $vietnamese = require lang_path("vi/{$group}.php");

        $this->assertSame([], $this->missingKeys($english, $vietnamese), "lang/vi/{$group}.php is missing keys.");
    }

    /**
     * Dotted paths of the keys in $reference that $candidate lacks, recursively.
     *
     * @param  array<string, mixed>  $reference
     * @param  array<string, mixed>  $candidate
     *
     * @return list<string>
     */
    private function missingKeys(array $reference, array $candidate, string $prefix = ''): array
    {
        $missing = [];

        foreach ($reference as $key => $value) {
            $path = $prefix.$key;

            if (!array_key_exists($key, $candidate)) {
                $missing[] = $path;
            } elseif (is_array($value) && is_array($candidate[$key])) {
                $missing = [...$missing, ...$this->missingKeys($value, $candidate[$key], $path.'.')];
            }
        }

        return $missing;
    }
}
