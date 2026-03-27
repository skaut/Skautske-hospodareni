<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\SelectFirstActiveRole;
use App\Model\User\Exception\UserHasNoRole;
use App\Model\User\UserService;

use function count;

final class SelectFirstActiveRoleHandler
{
    public function __construct(private UserService $userService)
    {
    }

    public function __invoke(SelectFirstActiveRole $command): void
    {
        $roles = $this->userService->getAllSkautisRoles();
        if (count($roles) === 0) {
            throw new UserHasNoRole();
        }

        $this->userService->updateSkautISRole($roles[0]->ID);
    }
}
