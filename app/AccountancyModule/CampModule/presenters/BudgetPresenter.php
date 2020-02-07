<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use App\AccountancyModule\EventModule\Components\MissingAutocomputedCategoryControl;
use App\AccountancyModule\EventModule\Factories\IMissingAutocomputedCategoryControlFactory;
use Model\Auth\Resources\Camp;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook\UpdateCampCategoryTotals;
use Model\Cashbook\MissingCategory;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use Model\Cashbook\ReadModel\Queries\InconsistentCampCategoryTotalsQuery;
use Model\Event\SkautisCampId;
use Model\Skautis\ReadModel\Queries\CampBudgetQuery;
use function count;

class BudgetPresenter extends BasePresenter
{
    /** @var IMissingAutocomputedCategoryControlFactory */
    private $missingAutocomputedCategoryControlFactory;

    public function __construct(IMissingAutocomputedCategoryControlFactory $missingAutocomputedCategoryControlFactory)
    {
        parent::__construct();
        $this->missingAutocomputedCategoryControlFactory = $missingAutocomputedCategoryControlFactory;
    }

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
        $this->setLayout('layout2');
        $campId = new SkautisCampId($aid);

        try {
            $inconsistentTotals = $this->queryBus->handle(new InconsistentCampCategoryTotalsQuery($campId));
            $this->template->setParameters([
                'isConsistent'             => count($inconsistentTotals) === 0,
                'toRepair'                 => $inconsistentTotals,
                'budgetEntries'            => $this->queryBus->handle(new CampBudgetQuery($campId)),
                'categoriesSummary'        => $this->queryBus->handle(new CategoriesSummaryQuery($this->getCashbookId($aid))),
                'isUpdateStatementAllowed' => $this->authorizator->isAllowed(Camp::UPDATE_BUDGET, $aid),
            ]);
            if (! $this->isAjax()) {
                return;
            }

            $this->redrawControl('contentSnip');
        } catch (MissingCategory $exc) {
            $this->template->setParameters(['missingCategories' => true]);
        }
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

    protected function createComponentCategoryAutocomputedControl() : MissingAutocomputedCategoryControl
    {
        return $this->missingAutocomputedCategoryControlFactory->create(new SkautisCampId($this->aid));
    }
}
