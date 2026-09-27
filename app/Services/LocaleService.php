<?php

namespace App\Services;

use App\Enums\Locale;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class LocaleService
{
    /** Name of the cookie that remembers the chosen language. */
    public const COOKIE = 'locale';

    /** One year, in minutes. */
    public const COOKIE_MINUTES = 60 * 24 * 365;

    /**
     * The language stored in the request's cookie.
     *
     * @param  Request  $request  The current request; its cookies are already decrypted.
     *
     * @return Locale|null Null when the cookie is missing or holds an unsupported value.
     */
    public function fromRequest(Request $request): ?Locale
    {
        $value = $request->cookie(self::COOKIE);

        return is_string($value) ? Locale::tryFrom($value) : null;
    }

    /**
     * Build the cookie that remembers the chosen language on this browser.
     *
     * @param  array{locale: string}  $data  Validated UpdateLocaleRequest data.
     */
    public function rememberCookie(array $data): Cookie
    {
        return cookie(self::COOKIE, $data['locale'], self::COOKIE_MINUTES);
    }

    /**
     * The flat dictionary the frontend translates with: English source
     * string → translation. English has no file, since its keys are the text.
     *
     * @param  string  $locale  The active locale.
     *
     * @return array<string, string>
     */
    public function dictionary(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (!is_file($path)) {
            return [];
        }

        /** @var array<string, string> $dictionary */
        $dictionary = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return $dictionary;
    }

    /**
     * A fingerprint of the locale's dictionary file, so a client drops the
     * copy it kept once the file changes, even without a new asset build.
     *
     * @param  string  $locale  The active locale.
     *
     * @return string A content hash, or 'none' when the locale has no file.
     */
    public function dictionaryVersion(string $locale): string
    {
        $path = lang_path("{$locale}.json");

        return is_file($path) ? (string) hash_file('xxh3', $path) : 'none';
    }
}
