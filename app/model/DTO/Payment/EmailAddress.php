<?php

declare(strict_types=1);

namespace Model\Common;

use InvalidArgumentException;
use Nette\Utils\Validators;
use function sprintf;
use function trim;

final class EmailAddress
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if (! Validators::isEmail($value)) {
            throw new InvalidArgumentException(sprintf("Value '%s' is not valid email!", $value));
        }
        $this->value = $value;
    }

    public function getValue() : string
    {
        return $this->value;
    }

    public function __toString() : string
    {
        return $this->getValue();
    }
}
