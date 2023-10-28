<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use Model\Auth\Resources\Education;
use Model\Auth\Resources\Grant;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook\UpdateEducationCategoryTotals;
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\InconsistentEducationCategoryTotalsQuery;
use Model\Event\SkautisEducationId;
use Model\Skautis\ReadModel\Queries\EducationBudgetQuery;

use function count;

class BudgetPresenter extends BasePresenter
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();

        if ($this->aid) {
            return;
        }

        $this->flashMessage('Musíš vybrat akci', 'danger');
        $this->redirect('Default:');
    }

    public function renderDefault(int $aid): void
    {
        if (! $this->authorizator->isAllowed(Education::ACCESS_BUDGET, $this->aid)) {
            $this->flashMessage('Nemáte právo prohlížet rozpočet akce', 'danger');
            $this->redirect('Education:', ['aid' => $aid]);
        }

        $educationId = new SkautisEducationId($aid);

        $inconsistentTotals = $this->queryBus->handle(new InconsistentEducationCategoryTotalsQuery($educationId, $this->event->startDate->year));
        $this->template->setParameters([
            'isConsistent'             => count($inconsistentTotals) === 0,
            'toRepair'                 => $inconsistentTotals,
            'budgetEntries'            => $this->queryBus->handle(new EducationBudgetQuery($educationId, $this->event->grantId)),
            'categoriesSummary'        => $this->queryBus->handle(new CategoriesSummaryQuery($this->getCashbookId($aid, $this->event->startDate->year))),
            'isUpdateStatementAllowed' => $this->event->grantId !== null && $this->authorizator->isAllowed(Grant::UPDATE_REAL_BUDGET_SPENDING, $this->event->grantId->toInt()),
        ]);
        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    /**
     * přepočte hodnoty v jednotlivých kategorich
     */
    public function handleConvert(int $aid): void
    {
        $this->editableOnly();

        $this->commandBus->handle(new UpdateEducationCategoryTotals($this->getCashbookId($aid, $this->event->startDate->year)));
        $this->flashMessage('Kategorie byly přepočítány.');

        if ($this->isAjax()) {
            $this->redrawControl('flash');
        } else {
            $this->redirect('this', $aid);
        }
    }

    private function getCashbookId(int $educationId, int $year): CashbookId
    {
        return $this->queryBus->handle(new EducationCashbookIdQuery(new SkautisEducationId($educationId), $year));
    }
}
