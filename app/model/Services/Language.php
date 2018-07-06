<?php

declare(strict_types=1);

namespace Model\Services;

use const LC_ALL;
use const LC_COLLATE;
use function setlocale;
use function strcoll;

class Language
{
    private const LOCALE = 'cs_CZ.UTF-8';

    public static function compare(string $first, $second) : int
    {
        $originalLocale = setlocale(LC_ALL, 0);
        setlocale(LC_COLLATE, self::LOCALE);
        $result = strcoll($first, $second);
        setlocale(LC_ALL, $originalLocale);

        return $result;
    }
}
