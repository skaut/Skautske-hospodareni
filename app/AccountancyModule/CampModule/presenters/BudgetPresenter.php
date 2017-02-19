<?php

namespace App\AccountancyModule\CampModule;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BudgetPresenter extends BasePresenter
{

    function startup()
    {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "danger");
            $this->redirect("Default:");
        }
    }

    function renderDefault($aid)
    {
        $toRepair = [];
        $this->template->isConsistent = $this->eventService->chits->isConsistent($aid, FALSE, $toRepair);
        $this->template->toRepair = $toRepair;
        $this->template->dataEstimate = $this->eventService->chits->getCategories($aid, TRUE);
        $this->template->dataReal = $this->eventService->chits->getCategories($aid, FALSE);
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    /**
     * přepočte hodnoty v jednotlivých kategorich
     * @param type $aid
     */
    public function handleConvert($aid)
    {
        $this->editableOnly();
        $this->eventService->chits->isConsistent($aid, $repair = TRUE);
        $this->flashMessage("Kategorie byly přepočítány.");

        if ($this->isAjax()) {
            //            $this->invalidateControl("paragony");
            $this->invalidateControl("flash");
        } else {
            $this->redirect('this', $aid);
        }
    }

}
