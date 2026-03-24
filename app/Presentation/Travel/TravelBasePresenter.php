<?php

declare(strict_types=1);

namespace App\Presentation\Travel;

use App\BaseSectionPresenter;
use App\Model\Unit\Unit;

class TravelBasePresenter extends BaseSectionPresenter
{
    protected Unit $officialUnit;

    protected function startup(): void
    {
        parent::startup();

        $this->officialUnit = $this->unitService->getOfficialUnit();
        $this->template->setParameters([
            'unit' => $this->officialUnit,
        ]);
    }

    protected function editableOnly(): void
    {
        if ($this->isEditable) {
            return;
        }

        $this->flashMessage('Akce je uzavřena a nelze ji upravovat.', 'danger');
        if ($this->isAjax()) {
            $this->sendPayload();
        } else {
            $this->redirect('Default:');
        }
    }
}
