<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Cashbook;

use App\Model\Cashbook\Commands\Cashbook\SelectFirstActiveRole;
use App\Model\Unit\UnitService;
use App\Model\User\Exception\UserHasNoRole;
use App\Model\User\UserService;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;

use function count;

final class SelectFirstActiveRoleHandler
{
    public function __construct(private UserService $userService, private UnitService $unitService, private User $user)
    {
    }

    public function __invoke(SelectFirstActiveRole $command): void
    {
        $roles = $this->userService->getAllSkautisRoles();
        if (count($roles) === 0) {
            throw new UserHasNoRole();
        }

        // Read before the switch: if SkautIS fails here, the role and the access stay unchanged both here and in SkautIS.
        $access = $this->userService->getAccessArrays($this->unitService, $roles, $roles[0]->ID);
        $this->userService->updateSkautISRole($roles[0]->ID);

        // The active role query reads the identity, so it must see the new role or the forwarded request fails again.
        $identity = $this->user->getIdentity();
        if (! $identity instanceof SimpleIdentity) {
            return;
        }

        $identity->currentRole = $this->userService->getActualRole($roles);
        $identity->skautisRoles = $roles;
        // The ended role's units must not stay editable.
        $identity->access = $access;
    }
}
