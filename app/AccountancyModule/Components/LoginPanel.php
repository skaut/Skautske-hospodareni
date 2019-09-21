<?php

declare(strict_types=1);

namespace App\Components;

use App\AccountancyModule\Components\BaseControl;
use Model\UnitService;
use Model\UserService;
use Nette\Security\Identity;
use Nette\Security\User;
use function assert;

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

        $identity = $this->user->getIdentity();

        assert($identity instanceof Identity);

        $identity->access      = $this->userService->getAccessArrays($this->unitService);
        $identity->currentRole = $this->userService->getActualRole();

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
