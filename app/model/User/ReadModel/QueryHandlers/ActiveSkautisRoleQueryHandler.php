<?php

declare(strict_types=1);

namespace Model\User\ReadModel\QueryHandlers;

use Model\Skautis\Exception\MissingCurrentRole;
use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\SkautisRole;
use Model\UserService;

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
