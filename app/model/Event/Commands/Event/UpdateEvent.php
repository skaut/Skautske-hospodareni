<?php

declare(strict_types=1);

namespace Model\Event\Commands\Event;

use Cake\Chronos\Date;
use Model\Event\Handlers\Event\UpdateEventHandler;
use Model\Event\SkautisEventId;

/**
 * @see UpdateEventHandler
 */
class UpdateEvent
{
    private SkautisEventId $eventId;

    private string $name;

    private Date $startDate;

    private Date $endDate;

    /** @var string|NULL */
    private $location;

    private int $scopeId;

    private int $typeId;

    public function __construct(SkautisEventId $eventId, string $name, Date $startDate, Date $endDate, ?string $location, int $scopeId, int $typeId)
    {
        $this->eventId   = $eventId;
        $this->name      = $name;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->location  = $location;
        $this->scopeId   = $scopeId;
        $this->typeId    = $typeId;
    }

    public function getEventId() : SkautisEventId
    {
        return $this->eventId;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getStartDate() : Date
    {
        return $this->startDate;
    }

    public function getEndDate() : Date
    {
        return $this->endDate;
    }

    public function getLocation() : ?string
    {
        return $this->location;
    }

    public function getScopeId() : int
    {
        return $this->scopeId;
    }

    public function getTypeId() : int
    {
        return $this->typeId;
    }
}
