<?php

declare(strict_types=1);

namespace Model\Event;

use Nette\SmartObject;

/**
 * @property-read Person|NULL $leader
 * @property-read Person|NULL $assistant
 * @property-read Person|NULL $accountant
 * @property-read Person|NULL $medic
 */
class Functions
{
    use SmartObject;

    private ?Person $leader = null;

    private ?Person $assistant = null;

    private ?Person $accountant = null;

    private ?Person $medic = null;

    public function __construct(?Person $leader, ?Person $assistant, ?Person $accountant, ?Person $medic)
    {
        $this->leader     = $leader;
        $this->assistant  = $assistant;
        $this->accountant = $accountant;
        $this->medic      = $medic;
    }

    public function getLeader() : ?Person
    {
        return $this->leader;
    }

    public function getAssistant() : ?Person
    {
        return $this->assistant;
    }

    public function getAccountant() : ?Person
    {
        return $this->accountant;
    }

    public function getMedic() : ?Person
    {
        return $this->medic;
    }
}
