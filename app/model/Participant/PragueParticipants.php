<?php

declare(strict_types=1);

namespace Model\Participant;

use Cake\Chronos\ChronosDate;
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
    public const PRAGUE_UNIT_PREFIX            = '11';

    /** @param Participant[] $participants */
    public static function fromParticipantList(ChronosDate $eventStartDate, array $participants): self
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

    public function __construct(private int $under18, private int $between18and26, private int $personDaysUnder26, private int $citizensCount)
    {
    }

    public function getUnder18(): int
    {
        return $this->under18;
    }

    public function getBetween18and26(): int
    {
        return $this->between18and26;
    }

    public function getPersonDaysUnder26(): int
    {
        return $this->personDaysUnder26;
    }

    public function getCitizensCount(): int
    {
        return $this->citizensCount;
    }

    public function isSupportable(int $totalDays): bool
    {
        return $this->under18 >= 8 && $totalDays >= 2 && $totalDays <= 6;
    }
}
