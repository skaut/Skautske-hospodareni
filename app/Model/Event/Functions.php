<?php

declare(strict_types=1);

namespace App\Model\Event;

use Nette\SmartObject;

/**
 * @property Person|null $leader
 * @property Person|null $assistant
 * @property Person|null $accountant
 * @property Person|null $medic
 */
class Functions
{
    use SmartObject;

    public function __construct(private ?Person $leader = null, private ?Person $assistant = null, private ?Person $accountant = null, private ?Person $medic = null)
    {
    }

    public function getLeader(): ?Person
    {
        return $this->leader;
    }

    public function getAssistant(): ?Person
    {
        return $this->assistant;
    }

    public function getAccountant(): ?Person
    {
        return $this->accountant;
    }

    public function getMedic(): ?Person
    {
        return $this->medic;
    }
}
