<?php

namespace Model\Cashbook\Cashbook;

use Nette\Utils\Strings;

final class ChitNumber
{

    public const PATTERN = '^[A-Z]{0,3}[0-9]{1,5}(/[0-9]{1,2})?$';

    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        $value = strtoupper($value);
        if(strlen($value) > 5 || ! Strings::match($value, sprintf('~%s~', self::PATTERN))) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not valid chit number', $value)
            );
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

}
