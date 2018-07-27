<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use function array_keys;
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

        $this->editableUnits = array_keys($this->unitService->getEditUnits($this->getUser()));
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
        $this->template->aid        = $this->aid;
        $this->template->isEditable = $this->isEditable;
    }


    /**
     * @return int[]
     */
    protected function getEditableUnits() : array
    {
        return $this->editableUnits;
    }
}
