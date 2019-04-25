<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\ReadModel\Queries\EditableUnitsQuery;
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

    protected function startup() : void
    {
        parent::startup();

        $this->aid = $this->aid ?? $this->unitService->getUnitId();

        $user          = $this->getUser();
        $readableUnits = $this->unitService->getReadUnits($user);

        $isReadable = isset($readableUnits[$this->aid]);

        $role                = $this->queryBus->handle(new ActiveSkautisRoleQuery());
        $this->editableUnits = array_keys($this->queryBus->handle(new EditableUnitsQuery($role)));
        $this->isEditable    = in_array($this->aid, $this->editableUnits);

        if ($isReadable) {
            return;
        }

        $this->flashMessage('Nemáte oprávnění pro zobrazení stránky', 'warning');
        $this->redirect(':Accountancy:Default:', ['aid' => null]);
    }

    protected function beforeRender() : void
    {
        parent::beforeRender();

        $presenterName = explode(':', $this->getName());

        $this->template->setParameters([
            'aid'        => $this->aid,
            'isEditable' => $this->isEditable,
            'presenterName' => $presenterName[array_key_last($presenterName)],
        ]);
    }

    /**
     * @return int[]
     */
    protected function getEditableUnits() : array
    {
        return $this->editableUnits;
    }
}
