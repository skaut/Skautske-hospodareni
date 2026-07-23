<?php

declare(strict_types=1);

namespace App\Presentation\Education\Budget;

use App\Model\Auth\Resources\Education;
use App\Model\Auth\Resources\Grant;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Commands\Cashbook\UpdateEducationCategoryTotals;
use App\Model\Cashbook\MissingCategory;
use App\Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\InconsistentEducationCategoryTotalsQuery;
use App\Model\Event\SkautisEducationId;
use App\Model\Skautis\ReadModel\Queries\EducationBudgetQuery;
use App\Presentation\Education\BasePresenter;
use LogicException;

use function count;

final class BudgetPresenter extends BasePresenter
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
        $budgetAvailable = $this->event->grantId !== null;
        $categoriesAvailable = $this->event->startDate !== null;

        try {
            $budgetEntries = $budgetAvailable
                ? $this->queryBus->handle(new EducationBudgetQuery($educationId, $this->event->grantId ?? throw new LogicException('Vzdělávací akce nemá přiřazenou dotaci.')))
                : [];
            $inconsistentTotals = $categoriesAvailable
                ? $this->queryBus->handle(new InconsistentEducationCategoryTotalsQuery($educationId, ($this->event->getStartDate() ?? throw new LogicException('Vzdělávací akce nemá datum zahájení.'))->year))
                : [];
            $categoriesSummary = $categoriesAvailable
                ? $this->queryBus->handle(new CategoriesSummaryQuery($this->getCashbookId($aid, $this->event->getStartDate()->year)))
                : [];
        } catch (MissingCategory $e) {
            $this->flashMessage($e->getMessage(), 'danger');
            $this->redirect('Education:', ['aid' => $aid]);
        }

        $this->template->setParameters([
            'budgetAvailable' => $budgetAvailable,
            'categoriesAvailable' => $categoriesAvailable,
            'isConsistent' => count($inconsistentTotals) === 0,
            'toRepair' => $inconsistentTotals,
            'budgetEntries' => $budgetEntries,
            'categoriesSummary' => $categoriesSummary,
            'isUpdateStatementAllowed' => $this->event->grantId !== null && $this->authorizator->isAllowed(Grant::UPDATE_REAL_BUDGET_SPENDING, $this->event->grantId->toInt()),
        ]);
        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    /**
     * přepočte hodnoty v jednotlivých kategorich.
     */
    public function handleConvert(int $aid): void
    {
        $this->editableOnly();

        $this->commandBus->handle(new UpdateEducationCategoryTotals($this->getCashbookId($aid, ($this->event->getStartDate() ?? throw new LogicException('Vzdělávací akce nemá datum zahájení.'))->year)));
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
