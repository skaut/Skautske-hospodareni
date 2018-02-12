<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

final class Recipient
{

    /** @var string */
    private $name;

    public function __construct(string $name)
    {
        if($name === '') {
            throw new \InvalidArgumentException('Recipient must have name');
        }

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }

}
