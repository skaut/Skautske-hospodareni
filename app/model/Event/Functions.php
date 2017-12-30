<?php

declare(strict_types=1);

namespace Model\Event;

class Functions
{

    /** @var Person|NULL */
    private $leader;

    /** @var Person|NULL */
    private $assistant;

    /** @var Person|NULL */
    private $accountant;

    /** @var Person|NULL */
    private $medic;

    public function __construct(?Person $leader, ?Person $assistant, ?Person $accountant, ?Person $medic)
    {
        $this->leader = $leader;
        $this->assistant = $assistant;
        $this->accountant = $accountant;
        $this->medic = $medic;
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
