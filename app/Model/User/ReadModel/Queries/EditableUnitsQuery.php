<?php

declare(strict_types=1);

namespace App\Model\User\ReadModel\Queries;

use App\Model\User\ReadModel\QueryHandlers\EditableUnitsQueryHandler;
use App\Model\User\SkautisRole;

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
