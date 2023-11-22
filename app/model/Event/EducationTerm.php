<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\ChronosDate;
use Nette\SmartObject;

use function array_unique;
use function count;

/**
 * @property-read SkautisEducationTermId $id
 * @property-read ChronosDate $startDate
 * @property-read ChronosDate $endDate
 * @property-read SkautisEducationLocationId $locationId
 */
class EducationTerm
{
    use SmartObject;

    public function __construct(
        private SkautisEducationTermId $id,
        private ChronosDate $startDate,
        private ChronosDate $endDate,
        private SkautisEducationLocationId $locationId,
    ) {
    }

    public function getId(): SkautisEducationTermId
    {
        return $this->id;
    }

    public function getStartDate(): ChronosDate
    {
        return $this->startDate;
    }

    public function getEndDate(): ChronosDate
    {
        return $this->endDate;
    }

    public function getLocationId(): SkautisEducationLocationId
    {
        return $this->locationId;
    }

    /** @param array<EducationTerm> $terms */
    public static function countTotalDays(array $terms): int
    {
        $days = [];

        foreach ($terms as $term) {
            $date = $term->startDate;

            while ($date->lessThanOrEquals($term->endDate)) {
                $days[] = $date;
                $date   = $date->addDay();
            }
        }

        return count(array_unique($days));
    }
}
