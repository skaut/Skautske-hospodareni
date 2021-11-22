<?php

declare(strict_types=1);

namespace Model\Payment;

use Exception;

use function sprintf;

class InvalidVariableSymbol extends Exception
{
    private string $invalidValue;

    public function __construct(string $invalidValue)
    {
        parent::__construct(sprintf('"%s" is not a valid variable symbol', $invalidValue));
        $this->invalidValue = $invalidValue;
    }

    public function getInvalidValue(): string
    {
        return $this->invalidValue;
    }
}
