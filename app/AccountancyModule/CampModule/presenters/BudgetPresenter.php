<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use Model\Auth\Resources\Camp;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotals;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\InconsistentCampCategoryTotalsQuery;
use Model\Event\SkautisCampId;
use Model\Skautis\ReadModel\Queries\CampBudgetQuery;
use function count;

class BudgetPresenter extends BasePresenter
{
    protected function startup() : void
    {
        parent::startup();
        if ($this->aid) {
            return;
        }

        $this->flashMessage('Musíš vybrat akci', 'danger');
        $this->redirect('Default:');
    }

    public function renderDefault(int $aid) : void
    {
        $campId = new SkautisCampId($aid);

        $inconistentTotals = $this->queryBus->handle(new InconsistentCampCategoryTotalsQuery($campId));

        $this->template->isConsistent             = count($inconistentTotals) === 0;
        $this->template->toRepair                 = $inconistentTotals;
        $this->template->budgetEntries            = $this->queryBus->handle(new CampBudgetQuery($campId));
        $this->template->categories               = $this->queryBus->handle(new CategoryListQuery($this->getCashbookId($aid)));
        $this->template->isUpdateStatementAllowed = $this->authorizator->isAllowed(Camp::UPDATE_BUDGET, $aid);
        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    /**
     * přepočte hodnoty v jednotlivých kategorich
     */
    public function handleConvert(int $aid) : void
    {
        $this->editableOnly();

        $this->commandBus->handle(new UpdateCampCategoryTotals($this->getCashbookId($aid)));
        $this->flashMessage('Kategorie byly přepočítány.');

        if ($this->isAjax()) {
            $this->redrawControl('flash');
        } else {
            $this->redirect('this', $aid);
        }
    }

    private function getCashbookId(int $campId) : CashbookId
    {
        return $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($campId)));
    }
}
