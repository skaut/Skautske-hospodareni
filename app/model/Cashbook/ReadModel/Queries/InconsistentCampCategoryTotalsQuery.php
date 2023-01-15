<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\InconsistentCampCategoryTotalsQueryQueryHandler;
use Model\Event\SkautisCampId;

/**
 * Returns categories with different total in app and Skautis
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
