<?php

declare(strict_types=1);

namespace Model\Payment;

use Nette\Utils\Strings;

final class VariableSymbol
{
    private const PATTERN = '^(?!0)[0-9]{1,10}$';

    public function __construct(private string $value)
    {
        if (! Strings::match($value, '/' . self::PATTERN . '/')) {
            throw new InvalidVariableSymbol($value);
        }
    }

    public function increment(): self
    {
        return new VariableSymbol(
            (string) ($this->toInt() + 1),
        );
    }

    public static function areEqual(VariableSymbol|null $first, VariableSymbol|null $second): bool
    {
        $firstInt  = $first?->toInt();
        $secondInt = $second?->toInt();

        return $firstInt === $secondInt;
    }

    public function toInt(): int
    {
        return (int) $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
