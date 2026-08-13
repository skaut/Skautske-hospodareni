<?php

declare(strict_types=1);

namespace App;

use Nette\Forms\Control;
use Nette\StaticClass;
use Nette\Utils\Validators;

use function array_intersect;
use function count;
use function explode;
use function preg_replace;
use function trim;

class MyValidators
{
    use StaticClass;

    public const EMAIL_SEPARATOR = ',';

    public static function isValidDate(mixed $control): bool
    {
        return $control->value !== null;
    }

    public static function isValidRange(Control $end, mixed $start): bool
    {
        return $start <= $end->getValue();
    }

    public static function hasSelectedAny(Control $control, mixed $values): bool
    {
        return count(array_intersect((array) $control->getValue(), (array) $values)) !== 0;
    }

    public static function isValidEmailList(mixed $control): bool
    {
        $value = preg_replace('/\s+/', '', $control->value);
        foreach (explode(self::EMAIL_SEPARATOR, $value) as $email) {
            if (! Validators::isEmail(trim($email))) {
                return false;
            }
        }

        return true;
    }
}
