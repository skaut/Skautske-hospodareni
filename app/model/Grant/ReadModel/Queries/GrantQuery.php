<?php

declare(strict_types=1);

namespace Model\Grant\ReadModel\Queries;

use Model\Grant\ReadModel\QueryHandlers\GrantQueryHandler;

/** @see GrantQueryHandler */
final class GrantQuery
{
    public function __construct(private int $grantId)
    {
    }

    public function getGrantId(): int
    {
        return $this->grantId;
    }
}
