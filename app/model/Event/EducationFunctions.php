<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/**
 * @property-read Person|NULL $leader
 * @property-read Person|NULL $accountant
 * @property-read Person|NULL $secretary
 * @property-read Person|NULL $medic
 * @property-read Person[] $assistants
 */
class EducationFunctions
{
    use SmartObject;

    public function __construct(private Person|null $leader = null, private Person|null $accountant = null, private Person|null $secretary = null, private Person|null $medic = null, private array $assistants = [])
    {
    }

    public function getLeader(): Person|null
    {
        return $this->leader;
    }

    public function getAccountant(): Person|null
    {
        return $this->accountant;
    }

    public function getSecretary(): Person|null
    {
        return $this->secretary;
    }

    public function getMedic(): Person|null
    {
        return $this->medic;
    }

    /**
     * @return Person[]
     */
    public function getAssistants(): array
    {
        return $this->assistants;
    }
}
