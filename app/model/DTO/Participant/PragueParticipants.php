<?php

declare(strict_types=1);

namespace Model\DTO\Participant;

class PragueParticipants
{
    /** @var int */
    private $under18;

    /** @var int */
    private $between18and26;

    /** @var int */
    private $personDaysUnder26;

    /** @var int */
    private $citizensCount;

    /** @var bool */
    private $isSupportable;

    public function __construct(int $under18, int $between18and26, int $personDaysUnder26, int $citizensCount, bool $supportable = false)
    {
        $this->under18           = $under18;
        $this->between18and26    = $between18and26;
        $this->personDaysUnder26 = $personDaysUnder26;
        $this->citizensCount     = $citizensCount;
        $this->isSupportable     = $supportable;
    }

    public function getUnder18() : int
    {
        return $this->under18;
    }

    public function getBetween18and26() : int
    {
        return $this->between18and26;
    }

    public function getPersonDaysUnder26() : int
    {
        return $this->personDaysUnder26;
    }

    public function getCitizensCount() : int
    {
        return $this->citizensCount;
    }

    public function isSupportable() : bool
    {
        return $this->isSupportable;
    }

    public function withSupportability(bool $supportable) : self
    {
        return new self($this->under18, $this->between18and26, $this->personDaysUnder26, $this->citizensCount, $supportable);
    }
}
