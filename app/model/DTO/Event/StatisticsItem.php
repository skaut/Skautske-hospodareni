<?php

declare(strict_types=1);

namespace Model\DTO\Event;

final class StatisticsItem
{
    public function __construct(private string $label, private int $count)
    {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
