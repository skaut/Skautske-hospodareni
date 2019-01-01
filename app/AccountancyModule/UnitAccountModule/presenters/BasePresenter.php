<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule;

use function array_key_exists;
use function date;

class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    /** @var int */
    protected $year;

    /** @var bool */
    protected $isReadable;

    protected function startup() : void
    {
        parent::startup();
        $this->type = 'unit';
        $this->aid  = $this->aid ?? $this->unitService->getUnitId();
        $this->year = $this->getParameter('year', date('Y'));

        $user          = $this->getUser();
        $readableUnits = $this->unitService->getReadUnits($user);

        $this->isReadable = $isReadable = isset($readableUnits[$this->aid]);
        $this->isEditable = array_key_exists($this->aid, $this->unitService->getEditUnits($user));

        if ($this->isEditable) {
            return;
        }

        $this->flashMessage('Nemáte oprávnění pro zobrazení stránky', 'warning');
        $this->redirect(':Accountancy:Default:', ['aid' => null]);
    }

    protected function beforeRender() : void
    {
        parent::beforeRender();
        $this->template->setParameters([
            'year'       => $this->year,
            'isEditable' => $this->isEditable,
            'aid'        => $this->aid,
        ]);
    }

    protected function editableOnly() : void
    {
        if ($this->isEditable) {
            return;
        }

        $this->flashMessage('Data jednotky jsou uzavřené a nelze je upravovat.', 'danger');
        if ($this->isAjax()) {
            $this->sendPayload();
        } else {
            $this->redirect('Default:');
        }
    }
}
