<?php

declare(strict_types=1);

namespace Model\Utils;

use function iconv;
use function preg_match;

class Strings
{
    public static function autoUTF(string $s): bool|string
    {
        // detect UTF-8
        if (preg_match('#[\x80-\x{1FF}\x{2000}-\x{3FFF}]#u', $s)) {
            return $s;
        }

        // detect WINDOWS-1250
        if (preg_match('#[\x7F-\x9F\xBC]#', $s)) {
            return iconv('WINDOWS-1250', 'UTF-8', $s);
        }

        // assume ISO-8859-2
        return iconv('ISO-8859-2', 'UTF-8', $s);
    }
}
