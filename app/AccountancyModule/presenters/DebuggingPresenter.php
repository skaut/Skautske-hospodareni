<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use Model\User\ReadModel\Queries\EditableUnitsQuery;

final class DebuggingPresenter extends BasePresenter
{
    public function renderDefault() : void
    {
        $user = $this->getUser();
        $role = $this->userService->getActualRole();

        $this->template->setParameters([
            'userId' => $user->getId(),
            'role' => $role,
            'readableUnits' => $this->unitService->getReadUnits($user),
            'editableUnits' => $this->queryBus->handle(new EditableUnitsQuery($role)),
        ]);
    }
}
