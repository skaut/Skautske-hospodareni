<?php

declare(strict_types=1);

namespace Model\DTO\Camp;

use Cake\Chronos\Date;

final class CampListItem
{
    private int $id;

    private string $name;

    private Date $startDate;

    private Date $endDate;

    private string $location;

    /** @var string|null */
    private $prefix;

    private string $state;

    public function __construct(
        int $id,
        string $name,
        Date $startDate,
        Date $endDate,
        string $location,
        ?string $prefix,
        string $state
    ) {
        $this->id        = $id;
        $this->name      = $name;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->location  = $location;
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

    public function getLocation() : string
    {
        return $this->location;
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
