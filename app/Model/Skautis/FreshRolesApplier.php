<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Cashbook\Commands\Cashbook\SelectFirstActiveRole;
use App\Model\Common\Services\CommandBus;
use App\Model\Skautis\Exception\MissingCurrentRole;
use App\Model\User\Exception\UserHasNoRole;
use App\Model\User\UserService;
use Nette\Security\SimpleIdentity;

/** Applies the roles that the last background SkautIS login extension loaded to the logged-in identity. */
final class FreshRolesApplier
{
    public function __construct(
        private SkautisLoginRefresher $skautisLoginRefresher,
        private UserService $userService,
        private CommandBus $commandBus,
    ) {
    }

    /**
     * @return bool true when the active role ended in SkautIS and the user was switched to another role
     *
     * @throws MissingCurrentRole when the user has no active role left
     */
    public function apply(SimpleIdentity $identity): bool
    {
        $roles = $this->skautisLoginRefresher->freshRoles();
        if ($roles === null) {
            return false;
        }

        $currentRole = $this->userService->getActualRole($roles);
        if ($currentRole === null) {
            $this->switchFromEndedRole();
        } else {
            $identity->skautisRoles = $roles;
            $identity->currentRole = $currentRole;
        }

        $this->skautisLoginRefresher->forgetFreshRoles();

        return $currentRole === null;
    }

    /**
     * The active role is no longer active in SkautIS. If switching fails, the exception leaves the fresh roles
     * unapplied, so the next request tries again.
     */
    private function switchFromEndedRole(): void
    {
        try {
            $this->commandBus->handle(new SelectFirstActiveRole());
        } catch (UserHasNoRole) {
            throw new MissingCurrentRole(); // ErrorPresenter shows the "no role" page
        }
    }
}
