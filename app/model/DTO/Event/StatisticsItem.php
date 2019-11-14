<?php

declare(strict_types=1);

namespace Model\DTO\Event;

final class StatisticsItem
{
    /** @var string */
    private $label;

    /** @var int */
    private $count;

    public function __construct(string $label, int $count)
    {
        $this->label = $label;
        $this->count = $count;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function getCount() : int
    {
        return $this->count;
    }
}
