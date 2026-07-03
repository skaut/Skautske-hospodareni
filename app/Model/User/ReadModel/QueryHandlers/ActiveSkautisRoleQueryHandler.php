<?php

declare(strict_types=1);

namespace App\Model\User\ReadModel\QueryHandlers;

use App\Model\Skautis\Exception\MissingCurrentRole;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use App\Model\User\SkautisRole;
use App\Model\User\UserService;

final class ActiveSkautisRoleQueryHandler
{
    public function __construct(private UserService $userService)
    {
    }

    public function __invoke(ActiveSkautisRoleQuery $_x): SkautisRole
    {
        $role = $this->userService->getActualRole();

        if ($role !== null) {
            return $role;
        }

        throw new MissingCurrentRole();
    }
}
