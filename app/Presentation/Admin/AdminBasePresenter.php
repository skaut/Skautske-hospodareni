<?php

declare(strict_types=1);

namespace App\Presentation\Admin;

use App\Model\Auth\Resources\Admin;
use App\Model\Common\UnitId;

abstract class AdminBasePresenter extends \App\BasePresenter
{
    protected UnitId $unitId;

    protected function startup(): void
    {
        parent::startup();

        if (! $this->getUser()->isLoggedIn()) {
            $backlink = $this->storeRequest('+ 3 days');
            if ($this->isAjax()) {
                $this->forward(':Auth:ajax', ['backlink' => $backlink]);
            } else {
                $this->redirect(':Default:', ['backlink' => $backlink]);
            }
        }

        if (! $this->authorizator->isAllowed(Admin::ACCESS, null)) {
            $this->flashMessage('Nemáte oprávnění vstoupit do administrace.', 'danger');
            $this->redirect(':Default:');
        }

        $unitId = $this->getParameter('unitId');
        $this->unitId = new UnitId($unitId !== null ? (int) $unitId : $this->unitService->getUnitId());
    }
}
