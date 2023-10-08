<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\Date;
use Nette\SmartObject;

use function array_unique;
use function count;

/**
 * @property-read SkautisEducationTermId $id
 * @property-read Date $startDate
 * @property-read Date $endDate
 * @property-read SkautisEducationLocationId $locationId
 */
class EducationTerm
{
    use SmartObject;

    public function __construct(
        private SkautisEducationTermId $id,
        private Date $startDate,
        private Date $endDate,
        private SkautisEducationLocationId $locationId,
    ) {
    }

    public function getId(): SkautisEducationTermId
    {
        return $this->id;
    }

    public function getStartDate(): Date
    {
        return $this->startDate;
    }

    public function getEndDate(): Date
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
