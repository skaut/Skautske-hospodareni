<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\ReadModel\QueryHandlers\InconsistentCampCategoryTotalsQueryQueryHandler;
use App\Model\Event\SkautisCampId;

/**
 * Returns categories with different total in app and Skautis.
 *
 * @see InconsistentCampCategoryTotalsQueryQueryHandler
 */
final class InconsistentCampCategoryTotalsQuery
{
    public function __construct(private SkautisCampId $campId)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }
}
