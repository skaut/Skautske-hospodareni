<?php

declare(strict_types=1);

namespace App\Model\Event;

use Nette\SmartObject;

/**
 * @property Person|null $leader
 * @property Person|null $accountant
 * @property Person|null $secretary
 * @property Person|null $medic
 * @property Person[]    $assistants
 */
class EducationFunctions
{
    use SmartObject;

    /** @param array<Person> $assistants */
    public function __construct(private ?Person $leader = null, private ?Person $accountant = null, private ?Person $secretary = null, private ?Person $medic = null, private array $assistants = [])
    {
    }

    public function getLeader(): ?Person
    {
        return $this->leader;
    }

    public function getAccountant(): ?Person
    {
        return $this->accountant;
    }

    public function getSecretary(): ?Person
    {
        return $this->secretary;
    }

    public function getMedic(): ?Person
    {
        return $this->medic;
    }

    /** @return Person[] */
    public function getAssistants(): array
    {
        return $this->assistants;
    }
}
