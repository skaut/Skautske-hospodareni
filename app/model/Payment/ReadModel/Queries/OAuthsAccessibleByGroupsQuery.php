<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\OAuthsAccessibleByGroupsQueryHandler;

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
