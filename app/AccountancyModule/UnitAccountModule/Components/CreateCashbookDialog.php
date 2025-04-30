<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule\Components;

use App\AccountancyModule\Components\Dialog;
use App\Forms\BaseForm;
use Model\Cashbook\Commands\Unit\CreateCashbook;
use Model\Cashbook\Commands\Unit\CreateUnit;
use Model\Cashbook\ReadModel\Queries\ActiveUnitCashbookQuery;
use Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use Model\Common\Services\CommandBus;
use Model\Common\Services\QueryBus;
use Model\Common\UnitId;
use Model\DTO\Cashbook\UnitCashbook;
use Nette\Utils\ArrayHash;

use function array_diff;
use function array_map;
use function date;
use function range;
use function Safe\array_combine;

final class CreateCashbookDialog extends Dialog
{
    private const YEARS_RANGE = [-5, 2];

    /** @var bool @persistent */
    public bool $opened = false;

    /** @var callable[] */
    public array $onSuccess = [];

    public function __construct(private bool $isEditable, private UnitId $unitId, private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__ . '/templates/CreateCashbookDialog.latte');
        $this->template->setParameters([
            'renderModal' => $this->opened,
        ]);
    }

    public function open(): void
    {
        $this->opened = true;
        $this->redrawControl();
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $yearsDescending = range($this->getYear(self::YEARS_RANGE[1]), $this->getYear(self::YEARS_RANGE[0]));
        $yearsDescending = array_diff($yearsDescending, $this->getYearsWithCashbook());

        $form->addSelect('year', 'Rok', array_combine($yearsDescending, $yearsDescending))
            ->setRequired('Musíte vybrat rok');

        $form->addSubmit('create', 'Založit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function ($form, ArrayHash $values): void {
            if (! $this->isEditable) {
                $this->flashMessage('Nemáte oprávnění přidávat pokladní knihy', 'danger');
                $this->redirect('this', ['opened' => false]);
            }

            $year = $values->year;

            $this->commandBus->handle(
                $this->unitExists()
                    ? new CreateCashbook($this->unitId, $year)
                    : new CreateUnit($this->unitId, $year),
            );
            $this->flashMessage('Pokladní kniha byla vytvořena');
            $this->onSuccess($year);
        };

        return $form;
    }

    private function getYear(int $yearsDifference): int
    {
        return (int) date('Y') + $yearsDifference;
    }

    /** @return int[] */
    private function getYearsWithCashbook(): array
    {
        $cashbooks = $this->queryBus->handle(new UnitCashbookListQuery($this->unitId));

        return array_map(function (UnitCashbook $cashbook): int {
            return $cashbook->getYear();
        }, $cashbooks);
    }

    private function unitExists(): bool
    {
        return $this->queryBus->handle(new ActiveUnitCashbookQuery($this->unitId)) !== null;
    }
}
