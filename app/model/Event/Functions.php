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

    public function __construct(private Person|null $leader = null, private Person|null $assistant = null, private Person|null $accountant = null, private Person|null $medic = null)
    {
    }

    public function getLeader(): Person|null
    {
        return $this->leader;
    }

    public function getAssistant(): Person|null
    {
        return $this->assistant;
    }

    public function getAccountant(): Person|null
    {
        return $this->accountant;
    }

    public function getMedic(): Person|null
    {
        return $this->medic;
    }
}
