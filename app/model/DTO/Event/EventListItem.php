<?php

declare(strict_types=1);

namespace Model\DTO\Event;

use Cake\Chronos\Date;

final class EventListItem
{
    private int $id;

    private string $name;

    private Date $startDate;

    private Date $endDate;

    private ?string $prefix;

    private string $state;

    public function __construct(int $id, string $name, Date $startDate, Date $endDate, ?string $prefix, string $state)
    {
        $this->id        = $id;
        $this->name      = $name;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->prefix    = $prefix;
        $this->state     = $state;
    }

    public function getId() : int
    {
        return $this->id;
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

    public function getPrefix() : ?string
    {
        return $this->prefix;
    }

    public function getState() : string
    {
        return $this->state;
    }
}
