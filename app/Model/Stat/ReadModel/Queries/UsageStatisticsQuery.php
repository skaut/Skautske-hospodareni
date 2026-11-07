<?php

declare(strict_types=1);

namespace App\Model\Stat\ReadModel\Queries;

use App\Model\Stat\ReadModel\QueryHandlers\UsageStatisticsQueryHandler;
use InvalidArgumentException;

/**
 * Logins and preferences of the whole system in one year or in one month of it.
 *
 * @see UsageStatisticsQueryHandler
 */
final class UsageStatisticsQuery
{
    public function __construct(private int $year, private ?int $month = null)
    {
        if ($month !== null && ($month < 1 || $month > 12)) {
            throw new InvalidArgumentException('Month must be between 1 and 12.');
        }
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }
}
