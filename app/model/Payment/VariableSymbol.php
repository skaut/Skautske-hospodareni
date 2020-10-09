<?php

declare(strict_types=1);

namespace Model\Payment;

use InvalidArgumentException;
use Nette\Utils\Strings;

final class VariableSymbol
{
    private string $value;

    private const PATTERN = '^(?!0)[0-9]{1,10}$';

    public function __construct(string $value)
    {
        if (! Strings::match($value, '/' . self::PATTERN . '/')) {
            throw new InvalidArgumentException("Invalid variable symbol '" . $value . "'");
        }
        $this->value = $value;
    }

    public function increment() : self
    {
        return new VariableSymbol(
            (string) ($this->toInt()+ 1)
        );
    }

    public static function areEqual(?VariableSymbol $first, ?VariableSymbol $second) : bool
    {
        $firstInt  = $first !== null ? $first->toInt() : null;
        $secondInt = $second !== null ? $second->toInt() : null;

        return $firstInt === $secondInt;
    }

    public function toInt() : int
    {
        return (int) $this->value;
    }

    public function __toString() : string
    {
        return $this->value;
    }
}
