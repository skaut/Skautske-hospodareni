<?php

declare(strict_types=1);

namespace Model\User\ReadModel\Queries;

use Model\User\ReadModel\QueryHandlers\ActiveSkautisRoleQueryHandler;
use Model\User\SkautisRole;

/**
 * @see ActiveSkautisRoleQueryHandler
 */
final class ActiveSkautisRoleQuery
{
    public const RETURN_TYPE = SkautisRole::class;
}
