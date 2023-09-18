<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\Date;
use Nette\SmartObject;

/**
 * @property-read SkautisEducationTermId $id
 * @property-read Date $startDate
 * @property-read Date $endDate
 */
class EducationTerm
{
    use SmartObject;

    public function __construct(
        private SkautisEducationTermId $id,
        private Date $startDate,
        private Date $endDate,
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
}
