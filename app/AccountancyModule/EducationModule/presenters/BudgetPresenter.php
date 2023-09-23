<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use Model\Auth\Resources\Education;
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
        $educationId = new SkautisEducationId($aid);

        $inconsistentTotals = $this->queryBus->handle(new InconsistentEducationCategoryTotalsQuery($educationId));
        $this->template->setParameters([
            'isConsistent'             => count($inconsistentTotals) === 0,
            'toRepair'                 => $inconsistentTotals,
            'budgetEntries'            => $this->queryBus->handle(new EducationBudgetQuery($educationId, $this->event->grantId)),
            'categoriesSummary'        => $this->queryBus->handle(new CategoriesSummaryQuery($this->getCashbookId($aid))),
            'isUpdateStatementAllowed' => $this->authorizator->isAllowed(Education::UPDATE_REAL_BUDGET_SPENDING, $aid),
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

        $this->commandBus->handle(new UpdateEducationCategoryTotals($this->getCashbookId($aid)));
        $this->flashMessage('Kategorie byly přepočítány.');

        if ($this->isAjax()) {
            $this->redrawControl('flash');
        } else {
            $this->redirect('this', $aid);
        }
    }

    private function getCashbookId(int $educationId): CashbookId
    {
        return $this->queryBus->handle(new EducationCashbookIdQuery(new SkautisEducationId($educationId)));
    }
}
