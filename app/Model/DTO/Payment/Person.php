<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

class Person
{
    /** @param MemberEmail[] $emails */
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

    /** @return MemberEmail[] */
    public function getEmails(): array
    {
        return $this->emails;
    }
}
