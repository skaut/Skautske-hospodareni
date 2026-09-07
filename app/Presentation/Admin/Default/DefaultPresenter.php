<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Default;

use App\Model\Auth\Resources\Admin;

final class DefaultPresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    /** @return string[] */
    protected function getRequiredAdminAccess(): array
    {
        return Admin::ANY_ACCESS;
    }

    public function actionDefault(): void
    {
        if (! $this->authorizator->isAllowed(Admin::ACCESS, null)) {
            $this->redirect(':Admin:BugReports:default');
        }
    }

    public function renderDefault(): void
    {
        $this->template->setParameters([
            'adminSection' => 'overview',
            'unitId' => $this->unitId->toInt(),
        ]);
    }
}
