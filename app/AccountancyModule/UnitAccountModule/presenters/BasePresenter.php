<?php

namespace App\AccountancyModule\UnitAccountModule;


/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter
{

    /** @persistent */
    public $aid;
    protected $year;

    /** @var bool */
    protected $isReadable;

    protected function startup() : void
    {
        parent::startup();
        $this->type = "unit";
        $this->aid = $this->aid ?? $this->unitService->getUnitId();
        $this->year = $this->getParameter("year", date("Y"));

        $user = $this->getUser();
        $readableUnits = $this->unitService->getReadUnits($user);

        $this->isReadable = $isReadable = isset($readableUnits[$this->aid]);
        $this->isEditable = array_key_exists($this->aid, $this->unitService->getEditUnits($user));

        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění pro zobrazení stránky", "warning");
            $this->redirect(":Accountancy:Default:", ["aid" => NULL]);
        }
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();
        $this->template->year = $this->year;
        $this->template->isEditable = $this->isEditable;
        $this->template->aid = $this->aid;
    }

    protected function editableOnly() : void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Data jednotky jsou uzavřené a nelze je upravovat.", "danger");
            if ($this->isAjax()) {
                $this->sendPayload();
            } else {
                $this->redirect("Default:");
            }
        }
    }

}
