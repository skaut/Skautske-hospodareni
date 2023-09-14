<?php

declare(strict_types=1);

namespace Model\DTO\Education;

use Cake\Chronos\Date;

final class EducationListItem
{
    public function __construct(
        private int $id,
        private string $name,
        private Date|null $startDate,
        private Date|null $endDate,
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

    public function getStartDate(): Date|null
    {
        return $this->startDate;
    }

    public function getEndDate(): Date|null
    {
        return $this->endDate;
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
