<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use Model\DTO\Payment\Group;
use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\ReadModel\Queries\EditableUnitsQuery;
use function array_intersect;
use function array_key_last;
use function array_keys;
use function explode;
use function in_array;

abstract class BasePresenter extends \App\AccountancyModule\BasePresenter
{
    /** @var bool */
    protected $isReadable;

    /** @var int[] */
    private $editableUnits;

    /** @var int[] */
    private $readableUnits;

    protected function startup() : void
    {
        parent::startup();

        $user          = $this->getUser();
        $readableUnits = $this->unitService->getReadUnits($user);

        $role = $this->queryBus->handle(new ActiveSkautisRoleQuery());

        $this->editableUnits = array_keys($this->queryBus->handle(new EditableUnitsQuery($role)));
        $this->readableUnits = array_keys($readableUnits);
        $this->isEditable    = in_array($this->unitId->toInt(), $this->editableUnits);

        if (isset($readableUnits[$this->unitId->toInt()])) {
            return;
        }

        $this->flashMessage('Nemáte oprávnění pro zobrazení stránky', 'warning');
        $this->redirect(':Accountancy:Default:', ['unitId' => null]);
    }

    protected function beforeRender() : void
    {
        parent::beforeRender();

        $presenterName = explode(':', $this->getName());

        $this->template->setParameters([
            'unitId'     => $this->unitId->toInt(),
            'isEditable' => $this->isEditable,
            'presenterName' => $presenterName[array_key_last($presenterName)],
        ]);
    }

    protected function hasAccessToGroup(Group $group) : bool
    {
        return array_intersect($group->getUnitIds(), $this->readableUnits) !== [];
    }

    protected function canEditGroup(Group $group) : bool
    {
        return array_intersect($group->getUnitIds(), $this->editableUnits) !== [];
    }

    /**
     * @return int[]
     */
    protected function getEditableUnits() : array
    {
        return $this->editableUnits;
    }
}
