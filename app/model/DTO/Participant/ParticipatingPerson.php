<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read string $firstName
 * @property-read string $lastName
 * @property-read string $nickName
 * @property-read string $displayName
 */
class ParticipatingPerson
{
    use SmartObject;

    public function __construct(
        private int $id,
        private string $firstName,
        private string $lastName,
        private string|null $nickName = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getNickName(): string|null
    {
        return $this->nickName;
    }

    public function getDisplayName(): string
    {
        return $this->lastName . ' ' . $this->firstName . ($this->nickName !== null ? '(' . $this->nickName . ')' : '');
    }
}
