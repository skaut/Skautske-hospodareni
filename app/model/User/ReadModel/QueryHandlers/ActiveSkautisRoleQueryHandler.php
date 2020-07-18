<?php

declare(strict_types=1);

namespace Model\User\ReadModel\QueryHandlers;

use Model\Skautis\Exception\MissingCurrentRole;
use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\SkautisRole;
use Model\UserService;

final class ActiveSkautisRoleQueryHandler
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function __invoke(ActiveSkautisRoleQuery $_) : SkautisRole
    {
        $role = $this->userService->getActualRole();

        if ($role !== null) {
            return $role;
        }

        throw new MissingCurrentRole();
    }
}
