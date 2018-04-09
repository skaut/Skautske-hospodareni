<?php

namespace App\AccountancyModule\CampModule;

use Model\Auth\Resources\Camp;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotals;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Event\SkautisCampId;

class BudgetPresenter extends BasePresenter
{

    protected function startup() : void
    {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "danger");
            $this->redirect("Default:");
        }
    }

    public function renderDefault(int $aid) : void
    {
        $toRepair = [];
        $this->template->isConsistent = $this->eventService->chits->isConsistent($aid, $toRepair);
        $this->template->toRepair = $toRepair;
        $this->template->dataEstimate = $this->eventService->chits->getCategories($aid, TRUE);
        $this->template->dataReal = $this->eventService->chits->getCategories($aid, FALSE);
        $this->template->isUpdateStatementAllowed = $this->authorizator->isAllowed(Camp::UPDATE_BUDGET, $aid);
        if ($this->isAjax()) {
            $this->redrawControl("contentSnip");
        }
    }

    /**
     * přepočte hodnoty v jednotlivých kategorich
     * @param int $aid
     */
    public function handleConvert(int $aid) : void
    {
        $this->editableOnly();

        $this->commandBus->handle(new UpdateCampCategoryTotals($this->getCashbookId($aid)));
        $this->flashMessage("Kategorie byly přepočítány.");

        if ($this->isAjax()) {
            $this->redrawControl("flash");
        } else {
            $this->redirect('this', $aid);
        }
    }

    private function getCashbookId(int $campId): CashbookId
    {
        return $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($campId)));
    }

}
