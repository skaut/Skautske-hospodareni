<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

class Person
{
    /** @param string[] $emails */
    public function __construct(private int $id, private string $name, private array $emails)
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

    /** @return string[] */
    public function getEmails(): array
    {
        return $this->emails;
    }
}
