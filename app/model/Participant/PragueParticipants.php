<?php

declare(strict_types=1);

namespace Model\Participant;

use Cake\Chronos\Date;
use Model\DTO\Participant\Participant;
use Nette\SmartObject;
use function stripos;

/**
 * @property-read int $under18
 * @property-read int $between18and26
 * @property-read int $personDaysUnder26
 * @property-read int $citizensCount
 */
final class PragueParticipants
{
    use SmartObject;

    private const PRAGUE_SUPPORTABLE_AGE       = 18;
    private const PRAGUE_SUPPORTABLE_UPPER_AGE = 26;
    public const PRAGUE_UNIT_PREFIX            = 11;

    /** @var int */
    private $under18;

    /** @var int */
    private $between18and26;

    /** @var int */
    private $personDaysUnder26;

    /** @var int */
    private $citizensCount;

    /**
     * @param Participant[] $participants
     */
    public static function fromParticipantList(Date $eventStartDate, array $participants) : self
    {
        $under18           = 0;
        $between18and26    = 0;
        $personDaysUnder26 = 0;
        $citizensCount     = 0;

        foreach ($participants as $p) {
            if (stripos($p->getCity(), 'Praha') === false) {
                continue;
            }
            $citizensCount += 1;

            $birthday = $p->getBirthday();

            if ($birthday === null) {
                continue;
            }

            $ageInYears = $eventStartDate->diffInYears($birthday);

            if ($ageInYears <= self::PRAGUE_SUPPORTABLE_AGE) {
                $under18 += 1;
            }

            if (self::PRAGUE_SUPPORTABLE_AGE < $ageInYears && $ageInYears <= self::PRAGUE_SUPPORTABLE_UPPER_AGE) {
                $between18and26 += 1;
            }

            if ($ageInYears > self::PRAGUE_SUPPORTABLE_UPPER_AGE) {
                continue;
            }

            $personDaysUnder26 += $p->getDays();
        }

        return new self($under18, $between18and26, $personDaysUnder26, $citizensCount);
    }

    public function __construct(int $under18, int $between18and26, int $personDaysUnder26, int $citizensCount)
    {
        $this->under18           = $under18;
        $this->between18and26    = $between18and26;
        $this->personDaysUnder26 = $personDaysUnder26;
        $this->citizensCount     = $citizensCount;
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

    public function isSupportable(int $totalDays) : bool
    {
        return $this->under18 >= 8 && $totalDays >= 2 && $totalDays <= 6;
    }
}
