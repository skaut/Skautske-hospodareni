<?php

declare(strict_types=1);

namespace Model\User\ReadModel\QueryHandlers;

use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\SkautisRole;
use Model\UserService;

final class ActiveSkautisRoleQueryHandler
{

    /** @var UserService */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function handle(ActiveSkautisRoleQuery $_): ?SkautisRole
    {
        return $this->userService->getActualRole();
    }

}
