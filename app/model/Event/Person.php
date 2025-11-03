<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/**
 * @property int         $id
 * @property string      $name
 * @property string|null $email
 */
class Person
{
    use SmartObject;

    public function __construct(private int $id, private string $name, private ?string $email = null)
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

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
