<?php

declare(strict_types=1);

namespace App\Components;

use App\AccountancyModule\Components\BaseControl;
use Model\UnitService;
use Model\UserService;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;

use function assert;

final class LoginPanel extends BaseControl
{
    private UserService $userService;

    private UnitService $unitService;

    private User $user;

    public function __construct(UserService $userService, UnitService $unitService, User $user)
    {
        $this->userService = $userService;
        $this->unitService = $unitService;
        $this->user        = $user;
    }

    public function handleChangeRole(int $roleId): void
    {
        $this->userService->updateSkautISRole($roleId);

        $identity = $this->user->getIdentity();

        assert($identity instanceof SimpleIdentity);

        $identity->access      = $this->userService->getAccessArrays($this->unitService);
        $identity->currentRole = $this->userService->getActualRole();

        $this->redirect('this');
    }

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/LoginPanel.latte');
        if ($this->user->isLoggedIn()) {
            $roles = [];

            foreach ($this->userService->getAllSkautisRoles() as $role) {
                $roles[$role->ID] = isset($role->RegistrationNumber) ? $role->RegistrationNumber . ' - ' . $role->Role : $role->Role;
            }

            $this->template->setParameters([
                'roles' => $roles,
                'currentRoleId' => $this->userService->getRoleId(),
            ]);
        }

        $this->template->render();
    }
}
