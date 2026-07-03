<?php

declare(strict_types=1);

namespace App\Model\Grant\ReadModel\Queries;

use App\Model\Grant\ReadModel\QueryHandlers\GrantQueryHandler;

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
