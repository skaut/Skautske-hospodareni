<?php

declare(strict_types=1);

namespace App\Presentation\Camps\Default;

use App\Components\Camps\CampListDataSource;
use App\Components\Camps\ExportDialog;
use App\Components\DataGrid;
use App\Components\Factories\Camps\IExportDialogFactory;
use App\Components\Grids\GridFactory;
use App\Model\Event\ReadModel\Queries\CampStates;
use Cake\Chronos\ChronosDate;

use function array_merge;

final class DefaultPresenter extends \App\BasePresenter
{
    public const DEFAULT_STATE = 'approvedParent';

    public function __construct(private readonly GridFactory $gridFactory, private readonly IExportDialogFactory $exportDialogFactory)
    {
        parent::__construct();
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(__DIR__.'/@campsGrid.latte');

        $grid->addColumnLink('name', 'Název', ':Camps:Detail:', null, ['aid' => 'id'])
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
            ->setCondition(function (CampListDataSource $dataSource, ?string $state): void {
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
