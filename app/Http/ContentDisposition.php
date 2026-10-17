<?php

declare(strict_types=1);

namespace App\Http;

use Nette\Utils\Strings;

use function preg_replace;
use function rawurlencode;
use function sprintf;

/**
 * Sestavuje hlavičku Content-Disposition tak, aby název souboru nemohl hlavičku rozbít
 * (uvozovky, zalomení řádku) a diakritika přežila přes `filename*` (RFC 6266).
 */
final class ContentDisposition
{
    public static function attachment(string $filename): string
    {
        return self::create('attachment', $filename);
    }

    public static function inline(string $filename): string
    {
        return self::create('inline', $filename);
    }

    private static function create(string $type, string $filename): string
    {
        $filename = (string) preg_replace('~[\x00-\x1F\x7F]~u', '', $filename);
        $fallback = (string) preg_replace('~[^\x20-\x7E]|["\\\\]~', '_', Strings::toAscii($filename));

        return sprintf('%s; filename="%s"; filename*=UTF-8\'\'%s', $type, $fallback, rawurlencode($filename));
    }
}
