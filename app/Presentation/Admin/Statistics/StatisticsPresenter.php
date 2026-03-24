<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Statistics;

use Component\Forms\BaseForm;
use App\Model\Stat\StatisticsService;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use Nette\Forms\Form;

use function date;

final class StatisticsPresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    protected ?int $year = null;

    public function __construct(private StatisticsService $statService)
    {
    }

    public function actionDefault(?int $year = null): void
    {
        $this->year = $year ?? (int) date('Y');
    }

    public function renderDefault(): void
    {
        $unit = $this->queryBus->handle(new UnitQuery($this->unitId->toInt()));
        $unitTree = $this->unitService->getTreeUnder($unit);
        $data = $this->statService->getEventStatistics($unitTree, $this->year);

        $this->template->setParameters([
            'adminSection' => 'statistics',
            'unitId' => $this->unitId->toInt(),
            'unit' => $unit,
            'unitTree' => $unitTree,
            'data' => $data,
        ]);
    }

    public function createComponentSelectYearForm(): Form
    {
        $form = new BaseForm();
        $form->addSelect('year', 'Rok', $this->getYearRange())
            ->setDefaultValue($this->year);
        $form->addSubmit('submit', 'Zobrazit');
        $form->onSuccess[] = function (Form $form): void {
            $this->redirect('this', ['year' => $form->getValues()->year]);
        };

        return $form;
    }

    /** @return array<int, int> */
    private function getYearRange(): array
    {
        $years = [];
        for ($i = (int) date('Y'); $i >= 2010; --$i) {
            $years[$i] = $i;
        }

        return $years;
    }
}
