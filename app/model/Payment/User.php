<?php

namespace Model\Payment;

class User
{

    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string|NULL */
    private $email;

    public function __construct(int $id, string $name, ?string $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
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
