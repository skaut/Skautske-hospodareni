<?php

/**
 * @author sinacek
 */
class Accountancy_Camp_BudgetPresenter extends Accountancy_Camp_BasePresenter {

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "error");
            $this->redirect("Default:");
        }
    }

    function renderDefault($aid) {
        $toRepair = array();
        $this->template->isConsistent   = $this->context->campService->chits->isConsistent($aid, false, $toRepair);
        $this->template->toRepair       = $toRepair;
        $this->template->dataEstimate   = $this->context->campService->chits->getCategoriesCamp($aid, true);
        $this->template->dataReal       = $this->context->campService->chits->getCategoriesCamp($aid);
    }

    /**
     * přepočte hodnoty v jednotlivých kategorich
     * @param type $aid 
     */
    public function handleConvert($aid) {
        $this->editableOnly();
        $this->context->campService->chits->isConsistent($aid, $repair = true);
        $this->flashMessage("Kategorie byly přepočítány.");

        if ($this->isAjax()) {
//            $this->invalidateControl("paragony");
            $this->invalidateControl("flash");
        } else {
            $this->redirect('this', $aid);
        }
    }

}

