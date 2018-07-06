<?php

namespace App\AccountancyModule\PaymentModule;

abstract class BasePresenter extends \App\AccountancyModule\BasePresenter
{

    /** @persistent */
    public $aid;

    /** @var bool */
    protected $isReadable;

    /** @var int[] */
    private $editableUnits;


    protected function startup() : void
    {
        parent::startup();

        $this->aid = $this->aid ?? $this->unitService->getUnitId();

        $user = $this->getUser();
        $readableUnits = $this->unitService->getReadUnits($user);

        $isReadable = isset($readableUnits[$this->aid]);

        $this->editableUnits = array_keys($this->unitService->getEditUnits($this->getUser()));
        $this->isEditable = in_array($this->aid, $this->editableUnits);

        if(!$isReadable) {
            $this->flashMessage("Nemáte oprávnění pro zobrazení stránky", "warning");
            $this->redirect(":Accountancy:Default:", ["aid" => NULL]);
        }
    }


    protected function beforeRender() : void
    {
        parent::beforeRender();
        $this->template->aid = $this->aid;
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
