<?php

declare(strict_types=1);

namespace Model\Event;

class Person
{

    /** @var int */
    private $id;

    /** @var string */
    private $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
