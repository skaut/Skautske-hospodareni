<?php

declare(strict_types=1);

namespace Model\DTO\Camp;

use Cake\Chronos\Date;

final class CampListItem
{
    public function __construct(
        private int $id,
        private string $name,
        private Date $startDate,
        private Date $endDate,
        private string $location,
        private string|null $prefix = null,
        private string $state,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartDate(): Date
    {
        return $this->startDate;
    }

    public function getEndDate(): Date
    {
        return $this->endDate;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getPrefix(): string|null
    {
        return $this->prefix;
    }

    public function getState(): string
    {
        return $this->state;
    }
}
