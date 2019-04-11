<?php

declare(strict_types=1);

namespace App\Components;

use App\AccountancyModule\Components\BaseControl;
use Model\UnitService;
use Model\UserService;
use Nette\Security\Identity;
use Nette\Security\User;

final class LoginPanel extends BaseControl
{
    /** @var UserService */
    private $userService;

    /** @var UnitService */
    private $unitService;

    /** @var User */
    private $user;

    public function __construct(UserService $userService, UnitService $unitService, User $user)
    {
        parent::__construct();
        $this->userService = $userService;
        $this->unitService = $unitService;
        $this->user        = $user;
    }

    public function handleChangeRole(int $roleId) : void
    {
        $this->userService->updateSkautISRole($roleId);

        /** @var Identity $identity */
        $identity = $this->user->getIdentity();

        $identity->access = $this->userService->getAccessArrays($this->unitService);

        $this->redirect('this');
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/LoginPanel.latte');

        $roles = [];

        foreach ($this->userService->getAllSkautisRoles() as $role) {
            $roles[$role->ID] = isset($role->RegistrationNumber) ? $role->RegistrationNumber . ' - ' . $role->Role : $role->Role;
        }

        $this->template->setParameters([
            'roles' => $roles,
            'currentRoleId' => $this->userService->getRoleId(),
        ]);

        $this->template->render();
    }
}
