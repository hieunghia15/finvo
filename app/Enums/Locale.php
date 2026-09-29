<?php

namespace App\Enums;

/**
 * Interface languages. The value is the Laravel locale and the name of the
 * `lang/` files; English needs no JSON file because its keys are the text.
 */
enum Locale: string
{
    case Vi = 'vi';
    case En = 'en';
}
