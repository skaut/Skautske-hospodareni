<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\ReadModel\QueryHandlers\InconsistentEducationCategoryTotalsQueryHandler;
use App\Model\Event\SkautisEducationId;

/**
 * Returns categories with different total in app and Skautis.
 *
 * @see InconsistentEducationCategoryTotalsQueryHandler
 */
final class InconsistentEducationCategoryTotalsQuery
{
    public function __construct(private SkautisEducationId $educationId, private int $year)
    {
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
