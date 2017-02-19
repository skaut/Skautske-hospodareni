<?php

namespace App\AccountancyModule\TravelModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BasePresenter extends \App\AccountancyModule\BasePresenter
{

    protected $unit;

    protected function startup()
    {
        parent::startup();
        $this->template->unit = $this->unit = $this->unitService->getOficialUnit();
    }

    protected function editableOnly()
    {
        throw new NotImplementedException("todo");
        //        if (!$this->isEditable) {
        //            $this->flashMessage("Akce je uzavřena a nelze ji upravovat.", "danger");
        //            if ($this->isAjax()) {
        //                $this->sendPayload();
        //            } else {
        //                $this->redirect("Default:");
        //            }
        //        }
    }

}
