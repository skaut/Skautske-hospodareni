<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\Date;
use Model\Common\UnitId;
use Model\Skautis\ISkautisEvent;
use Nette\NotImplementedException;
use Nette\SmartObject;

/**
 * @property-read SkautisCampId $id
 * @property-read string $displayName
 * @property-read Date $startDate
 * @property-read Date $endDate
 * @property-read string $state
 */
class Education implements ISkautisEvent
{
    use SmartObject;

    private SkautisEducationId $id;

    private string $displayName;

    private Date $startDate;

    private Date $endDate;

    private string $state;

    public function __construct(
        SkautisEducationId $id,
        string $displayName,
        Date $startDate,
        Date $endDate,
        string $state
    ) {
        $this->id          = $id;
        $this->displayName = $displayName;
        $this->startDate   = $startDate;
        $this->endDate     = $endDate;
        $this->state       = $state;
    }

    public function getId() : SkautisEducationId
    {
        return $this->id;
    }

    public function getDisplayName() : string
    {
        return $this->displayName;
    }

    public function getUnitId() : UnitId
    {
        throw new NotImplementedException('For education events is not implemented UnitID');
    }

    public function getState() : string
    {
        return $this->state;
    }

    public function getStartDate() : Date
    {
        return $this->startDate;
    }

    public function getEndDate() : Date
    {
        return $this->endDate;
    }
}
