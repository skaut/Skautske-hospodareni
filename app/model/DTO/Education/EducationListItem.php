<?php

declare(strict_types=1);

namespace Model\DTO\Education;

use Cake\Chronos\ChronosDate;

final class EducationListItem
{
    public function __construct(
        private int $id,
        private string $name,
        private ChronosDate|null $startDate,
        private ChronosDate|null $endDate,
        private string|null $prefix = null,
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

    public function getStartDate(): ChronosDate|null
    {
        return $this->startDate;
    }

    public function getEndDate(): ChronosDate|null
    {
        return $this->endDate;
    }

    public function getPrefix(): string|null
    {
        return $this->prefix;
    }
}
