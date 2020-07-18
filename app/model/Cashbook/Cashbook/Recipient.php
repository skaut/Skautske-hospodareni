<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use InvalidArgumentException;

final class Recipient
{
    private string $name;

    public function __construct(string $name)
    {
        if ($name === '') {
            throw new InvalidArgumentException('Recipient must have name');
        }

        $this->name = $name;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function __toString() : string
    {
        return $this->name;
    }
}
