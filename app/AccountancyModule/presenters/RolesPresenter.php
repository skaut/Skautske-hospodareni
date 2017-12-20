<?php

namespace App\AccountancyModule;

class RolesPresenter extends BasePresenter
{

    public function renderDefault(): void
    {
        $roles = array_map(function (\stdClass $role) {
            return [
                'id' => $role->ID,
                'name' => ($role->RegistrationNumber ? ($role->RegistrationNumber . ' - ') : '') . $role->Role,
            ];
        }, $this->userService->getAllSkautisRoles());

        $this->sendJson([
            'roles' => $roles,
            'active_role_id' => $this->userService->getRoleId(),
            'change_link' => $this->link('changeRole!'),
        ]);
    }

}
