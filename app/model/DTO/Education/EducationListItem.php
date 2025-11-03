<?php

declare(strict_types=1);

namespace Model\DTO\Education;

use Cake\Chronos\ChronosDate;

final class EducationListItem
{
    public function __construct(
        private int $id,
        private string $name,
        private ?ChronosDate $startDate,
        private ?ChronosDate $endDate,
        private ?string $prefix,
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

    public function getStartDate(): ?ChronosDate
    {
        return $this->startDate;
    }

    public function getEndDate(): ?ChronosDate
    {
        return $this->endDate;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }
}
