<?php

namespace Model\Cashbook\Cashbook;

final class Recipient
{

    /** @var string */
    private $name;

    public function __construct(string $name)
    {
        if(strlen($name) === 0) {
            throw new \InvalidArgumentException('Recipient must have name');
        }

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
