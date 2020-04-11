<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule;

use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\ReadModel\Queries\EditableUnitsQuery;
use function array_key_exists;
use function array_key_last;
use function date;
use function explode;

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
        $this->year = (int) $this->getParameter('year', date('Y'));

        $user          = $this->getUser();
        $readableUnits = $this->unitService->getReadUnits($user);

        $this->isReadable = $isReadable = isset($readableUnits[$this->unitId->toInt()]);

        $role             = $this->queryBus->handle(new ActiveSkautisRoleQuery());
        $this->isEditable = array_key_exists($this->unitId->toInt(), $this->queryBus->handle(new EditableUnitsQuery($role)));

        if ($this->isEditable) {
            return;
        }
        $this->setView('accessDenied');
    }

    protected function beforeRender() : void
    {
        parent::beforeRender();

        $presenterName = explode(':', $this->getName());

        $this->template->setParameters([
            'year'       => $this->year,
            'isEditable' => $this->isEditable,
            'unitId'     => $this->unitId->toInt(),
            'presenterName' => $presenterName[array_key_last($presenterName)],
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
