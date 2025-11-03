<?php

declare(strict_types=1);

namespace Model\Common;

class User
{
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
