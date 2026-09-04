<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Usage;

use App\Model\Stat\ReadModel\Queries\UsageStatisticsQuery;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Unit\Services\UnitTreeSelectOptions;
use Component\Forms\BaseForm;
use Nette\Forms\Form;

use function date;

/**
 * Usage of the application itself, kept apart from Admin:Statistics on purpose:
 * that page answers "what is in the system", this one answers "who uses it".
 * It also stays free of skautIS calls, so it loads out of the database alone.
 */
final class UsagePresenter extends \App\Presentation\Admin\AdminBasePresenter
{
    private const FIRST_YEAR = 2010;

    protected ?int $year = null;

    public function __construct(private UnitTreeSelectOptions $unitTreeSelectOptions)
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

        $this->template->setParameters([
            'adminSection' => 'usage',
            'unitId' => $this->unitId->toInt(),
            'unit' => $unit,
            'year' => $this->year,
            'usage' => $this->queryBus->handle(
                new UsageStatisticsQuery($unitTree->getIdWithChildren(), (int) $this->year),
            ),
        ]);
    }

    public function createComponentSelectYearForm(): Form
    {
        $form = new BaseForm();
        $form->addSelect('unitId', 'Jednotka', $this->unitTreeSelectOptions->getOptions())
            ->setDefaultValue($this->unitId->toInt());
        $form->addSelect('year', 'Rok', $this->getYearRange())
            ->setDefaultValue($this->year);
        $form->addSubmit('submit', 'Zobrazit');
        $form->onSuccess[] = function (Form $form): void {
            $values = $form->getValues();
            $this->redirect('this', [
                'unitId' => (int) $values->unitId,
                'year' => (int) $values->year,
            ]);
        };

        return $form;
    }

    /** @return array<int, int> */
    private function getYearRange(): array
    {
        $years = [];
        for ($i = (int) date('Y'); $i >= self::FIRST_YEAR; --$i) {
            $years[$i] = $i;
        }

        return $years;
    }
}
