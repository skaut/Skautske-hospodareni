<?php

declare(strict_types=1);

namespace App\Components;

use App\Model\Unit\UnitService;
use App\Model\User\UserPreferencesService;
use App\Model\User\UserService;
use LogicException;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;

final class LoginPanel extends BaseControl
{
    public function __construct(
        private UserService $userService,
        private UnitService $unitService,
        private UserPreferencesService $preferences,
        private User $user,
    ) {
    }

    public function handleChangeRole(int $roleId): void
    {
        // Loaded before the switch: if it fails, the role stays unchanged both here and in SkautIS.
        $roles = $this->userService->getAllSkautisRoles();
        $this->userService->updateSkautISRole($roleId);
        $currentRoleId = $this->userService->getRoleId();
        if ($currentRoleId !== null) {
            $this->preferences->rememberCurrentSkautisRole($currentRoleId);
        }

        $identity = $this->user->getIdentity();

        if (! $identity instanceof SimpleIdentity) {
            throw new LogicException('Assertion failed.');
        }
        // Stored before access, which calls SkautIS again: the active role then matches SkautIS even when that call fails.
        $identity->currentRole = $this->userService->getActualRole($roles);
        $identity->skautisRoles = $roles;
        $identity->access = $this->userService->getAccessArrays($this->unitService, $roles);

        $this->redirect('this');
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/LoginPanel.latte');
        if ($this->user->isLoggedIn()) {
            $identity = $this->user->getIdentity();
            $roles = [];

            // Roles come from the identity (set at login and role change), so rendering makes no SkautIS call.
            foreach ($identity instanceof SimpleIdentity ? $identity->skautisRoles ?? [] : [] as $role) {
                $roles[$role->ID] = $role->DisplayName;
            }

            $this->template->setParameters([
                'roles' => $roles,
                'currentRoleId' => $this->userService->getRoleId(),
            ]);
        }

        $this->template->render();
    }
}
