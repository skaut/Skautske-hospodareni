<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\InconsistentEducationCategoryTotalsQueryHandler;
use Model\Event\SkautisEducationId;

/**
 * Returns categories with different total in app and Skautis
 *
 * @see InconsistentEducationCategoryTotalsQueryHandler
 */
final class InconsistentEducationCategoryTotalsQuery
{
    public function __construct(private SkautisEducationId $educationId)
    {
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }
}
