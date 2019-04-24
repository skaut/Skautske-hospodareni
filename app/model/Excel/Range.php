<?php

declare(strict_types=1);

namespace Model\Excel;

use Nette\StaticClass;

final class Range
{
    use StaticClass;

    /**
     * This method is workaround for static analysis bug
     * @see https://github.com/phpstan/phpstan/issues/1201
     * @return string[]
     */
    public static function letters(string $firstLetter, string $lastLetter) : array
    {
        /** @var string[] $range */
        $range = range($firstLetter, $lastLetter);

        return $range;
    }
}
