<?php

namespace Model\Payment;

use Nette\Utils\Strings;

final class VariableSymbol
{

    /** @var string */
    private $value;

    public const PATTERN = '^(?!0)[0-9]{1,10}$';

    public function __construct(string $value)
    {
        if( ! Strings::match($value, '/' . self::PATTERN . '/')) {
            throw new \InvalidArgumentException("Invalid variable symbol '$value'");
        }
        $this->value = $value;
    }

    public function increment(): self
    {
        $numericValue = (int)$this->value + 1;
        $length = strlen($this->value);
        $prefixedValue = str_pad($numericValue, $length, '0', STR_PAD_LEFT);

        return new VariableSymbol($prefixedValue);
    }

    public function equals(VariableSymbol $other): bool
    {
        return $other->toInt() === $this->toInt();
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
