<?php

namespace Model\Cashbook\Cashbook;

use Nette\Utils\Strings;

final class ChitNumber
{

    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        if(!Strings::match($value, '~[0-9]{1,5}~')) {
            throw new \InvalidArgumentException('Chit number must be numeric value with 1-5 symbols');
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

}
