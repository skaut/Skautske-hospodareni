<?php

declare(strict_types=1);

namespace Model\Participant;


use Nette\SmartObject;

/**
 * @property-read int $under18
 * @property-read int $between18and26
 * @property-read int $personDaysUnder26
 * @property-read int $citizensCount
 */
final class PragueParticipants
{
    use SmartObject;

    /** @var int */
    private $under18;

    /** @var int */
    private $between18and26;

    /** @var int */
    private $personDaysUnder26;

    /** @var int */
    private $citizensCount;

    public function __construct(int $under18, int $between18and26, int $personDaysUnder26, int $citizensCount)
    {
        $this->under18           = $under18;
        $this->between18and26    = $between18and26;
        $this->personDaysUnder26 = $personDaysUnder26;
        $this->citizensCount     = $citizensCount;
    }

    public function isSupportable($totalDays) : bool
    {
        return $this->under18 >= 8 && $totalDays >= 2 && $totalDays <= 6;
    }
}
