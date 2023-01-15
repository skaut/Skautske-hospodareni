<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

class Statistics
{
    public function __construct(private int $personDays, private int $personsCount)
    {
    }

    public function getPersonDays(): int
    {
        return $this->personDays;
    }

    public function getPersonsCount(): int
    {
        return $this->personsCount;
    }
}
