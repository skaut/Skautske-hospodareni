<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use App\AccountancyModule\CampModule\Components\ExportDialog;
use App\AccountancyModule\CampModule\Factories\IExportDialogFactory;
use App\AccountancyModule\Components\DataGrid;
use App\AccountancyModule\Factories\GridFactory;
use Cake\Chronos\ChronosDate;
use Model\Event\ReadModel\Queries\CampStates;

use function array_merge;

class DefaultPresenter extends BasePresenter
{
    public const DEFAULT_STATE = 'approvedParent'; //filtrovani zobrazených položek

    public function __construct(private GridFactory $gridFactory, private IExportDialogFactory $exportDialogFactory)
    {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(__DIR__ . '/../templates/@campsGrid.latte');

        $grid->addColumnLink('name', 'Název', 'Detail:', null, ['aid' => 'id'])
            ->setSortable();

        $grid->addColumnDateTime('startDate', 'Začátek')
            ->setSortable();

        $grid->addColumnDateTime('endDate', 'Konec')
            ->setSortable();

        $grid->addColumnText('location', 'Místo')
            ->setSortable();

        $grid->addColumnText('prefix', 'Prefix')
            ->setSortable();

        $grid->addColumnText('state', 'Stav');

        $grid->addYearFilter('year', 'Rok')
            ->setCondition(function (CampListDataSource $dataSource, $year): void {
                $dataSource->filterByYear($year === DataGrid::OPTION_ALL ? null : (int) ($year ?? ChronosDate::today()->year));
            });

        $states = array_merge([DataGrid::OPTION_ALL => 'Nezrušené'], $this->queryBus->handle(new CampStates()));
        $grid->addFilterSelect('state', 'Stav', $states)
            ->setCondition(function (CampListDataSource $dataSource, string|null $state): void {
                $dataSource->filterByState($state === DataGrid::OPTION_ALL ? null : $state);
            });

        $grid->addFilterText('search', 'Název', ['name', 'location'])
            ->setPlaceholder('Název, místo...');

        $grid->setDataSource(new CampListDataSource($this->queryBus));
        $grid->setDefaultSort(['startDate' => 'ASC']);

        $grid->setDefaultFilter([
            'search' => '',
            'year' => (string) ChronosDate::today()->year,
            'state' => self::DEFAULT_STATE,
        ]);

        return $grid;
    }

    protected function createComponentExportDialog(): ExportDialog
    {
        return $this->exportDialogFactory->create($this['grid']->getFilteredAndSortedData());
    }
}
