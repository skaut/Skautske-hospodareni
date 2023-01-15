<?php

declare(strict_types=1);

namespace Model\User\ReadModel\Queries;

use Model\User\ReadModel\QueryHandlers\EditableUnitsQueryHandler;
use Model\User\SkautisRole;

/** @see EditableUnitsQueryHandler */
final class EditableUnitsQuery
{
    public function __construct(private SkautisRole $role)
    {
    }

    public function getRole(): SkautisRole
    {
        return $this->role;
    }
}
