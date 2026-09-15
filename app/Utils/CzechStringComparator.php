<?php

declare(strict_types=1);

namespace App\Utils;

use Collator;
use DateTimeInterface;
use Stringable;

use function class_exists;
use function is_numeric;
use function is_string;
use function setlocale;
use function strcoll;
use function strtoupper;

use const LC_COLLATE;

final class CzechStringComparator
{
    private static ?Collator $collator = null;

    private function __construct()
    {
    }

    public static function compare(string $left, string $right): int
    {
        if (class_exists(Collator::class)) {
            self::$collator ??= new Collator('cs_CZ');

            return (int) self::$collator->compare($left, $right);
        }

        setlocale(LC_COLLATE, 'cs_CZ.utf8');

        return strcoll($left, $right);
    }

    public static function compareValues(mixed $left, mixed $right): int
    {
        if ($left === $right) {
            return 0;
        }

        if ($left === null) {
            return -1;
        }

        if ($right === null) {
            return 1;
        }

        if ($left instanceof DateTimeInterface && $right instanceof DateTimeInterface) {
            return $left <=> $right;
        }

        if (is_numeric($left) && is_numeric($right)) {
            return $left <=> $right;
        }

        if (is_string($left) || is_string($right) || $left instanceof Stringable || $right instanceof Stringable) {
            return self::compare((string) $left, (string) $right);
        }

        return $left <=> $right;
    }

    public static function applyDirection(int $result, string $direction): int
    {
        return strtoupper($direction) === 'DESC' ? -$result : $result;
    }
}
