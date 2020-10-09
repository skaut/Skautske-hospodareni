<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

class Statistics
{
    private int $personDays;

    private int $personsCount;

    public function __construct(int $personDays, int $personsCount)
    {
        $this->personDays   = $personDays;
        $this->personsCount = $personsCount;
    }

    public function getPersonDays() : int
    {
        return $this->personDays;
    }

    public function getPersonsCount() : int
    {
        return $this->personsCount;
    }
}
