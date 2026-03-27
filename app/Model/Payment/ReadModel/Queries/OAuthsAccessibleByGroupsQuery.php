<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\Queries;

use App\Model\Payment\ReadModel\QueryHandlers\OAuthsAccessibleByGroupsQueryHandler;

/** @see OAuthsAccessibleByGroupsQueryHandler */
final class OAuthsAccessibleByGroupsQuery
{
    /** @param int[] $unitIds */
    public function __construct(private array $unitIds)
    {
    }

    /** @return int[] */
    public function getUnitIds(): array
    {
        return $this->unitIds;
    }
}
