<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

/** @see PaymentGroupStatisticsQueryHandler */
final class PaymentGroupStatisticsQuery
{
    /** @param int[] $unitIds */
    public function __construct(private array $unitIds, private int $year)
    {
    }

    /** @return int[] */
    public function getUnitIds(): array
    {
        return $this->unitIds;
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
