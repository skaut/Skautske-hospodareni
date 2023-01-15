<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/**
 * @property-read int           $id
 * @property-read string        $name
 * @property-read string|NULL   $email
 */
class Person
{
    use SmartObject;

    public function __construct(private int $id, private string $name, private string|null $email = null)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string|null
    {
        return $this->email;
    }
}
