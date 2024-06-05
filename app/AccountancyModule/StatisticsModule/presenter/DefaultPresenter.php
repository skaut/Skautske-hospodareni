<?php

declare(strict_types=1);

namespace App\AccountancyModule\StatisticsModule;

use App\Forms\BaseForm;
use Model\StatisticsService;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Nette\Forms\Form;

use function date;

class DefaultPresenter extends BasePresenter
{
    protected int|null $year;

    public function __construct(private StatisticsService $statService)
    {
        parent::__construct();
    }

    public function actionDefault(int|null $year = null): void
    {
        if ($year === null) {
            $year = (int) date('Y');
        }

        $this->year = $year;
    }

    public function renderDefault(int|null $year = null): void
    {
        $unit     = $this->queryBus->handle(new UnitQuery($this->unitId->toInt()));
        $unitTree = $this->unitService->getTreeUnder($unit);
        $data     = $this->statService->getEventStatistics($unitTree, $this->year);

        $this->template->setParameters([
            'unit' => $unit,
            'unitTree' => $unitTree,
            'data' => $data,
        ]);
    }

    /** @return array<int, string> */
    private function getYearRange(): array
    {
        $years = [];
        for ($i = date('Y'); $i >= 2010; $i--) {
            $years[$i] = $i;
        }

        return $years;
    }

    public function createComponentSelectYearForm(): Form
    {
        $form = new BaseForm();
        $form->addSelect('year', 'Rok', $this->getYearRange())->setDefaultValue($this->year);
        $form->addSubmit('submit', 'Zobrazit');
        $form->onSuccess[] = function (Form $form): void {
            $this->redirect('this', ['year' => $form->getValues()->year]);
        };

        return $form;
    }
}
