<?php

declare(strict_types=1);

namespace App\Model\Usage\ReadModel\Queries;

use App\Model\Usage\ReadModel\QueryHandlers\SystemUsageQueryHandler;
use App\Model\Usage\UsageUnitCode;
use InvalidArgumentException;

/**
 * Sessions in the system in one year or in one month of it: in the whole system with its kraje, in one kraj
 * with its okresy, or in one okres with its střediska.
 *
 * @see SystemUsageQueryHandler
 */
final class SystemUsageQuery
{
    /**
     * @param string|null $region   kraj, e.g. 620; null for the whole system
     * @param string|null $district okres, e.g. 621, or the kraj itself for its roles and direct střediska
     */
    public function __construct(
        private int $year,
        private ?int $month = null,
        private ?string $region = null,
        private ?string $district = null,
    ) {
        if ($month !== null && ($month < 1 || $month > 12)) {
            throw new InvalidArgumentException('Month must be between 1 and 12.');
        }

        if ($region !== null && ! UsageUnitCode::isRegion($region)) {
            throw new InvalidArgumentException('Unknown kraj '.$region.'.');
        }

        if ($district !== null && ! UsageUnitCode::isDistrict($district)) {
            throw new InvalidArgumentException('Unknown okres '.$district.'.');
        }

        if ($region !== null && $district !== null && UsageUnitCode::regionOf($district) !== $region) {
            throw new InvalidArgumentException('Okres '.$district.' is not in kraj '.$region.'.');
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

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }
}
