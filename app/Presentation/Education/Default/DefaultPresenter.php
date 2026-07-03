<?php

declare(strict_types=1);

namespace App\Presentation\Education\Default;

use App\Components\DataGrid;
use App\Components\Education\EducationListDataSource;
use App\Components\Grids\GridFactory;
use Cake\Chronos\ChronosDate;

final class DefaultPresenter extends \App\BasePresenter
{
    public function __construct(private readonly GridFactory $gridFactory)
    {
        parent::__construct();
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(__DIR__.'/@eventsGrid.latte', []);

        $grid->addColumnLink('name', 'Název', ':Education:Education:', null, ['aid' => 'id'])
            ->setSortable();

        $grid->addColumnDateTime('startDate', 'Začátek akce')
            ->setSortable();

        $grid->addColumnDateTime('endDate', 'Konec akce')
            ->setSortable();

        $grid->addYearFilter('year', 'Rok')
            ->setCondition(function (EducationListDataSource $dataSource, $year): void {
                $dataSource->filterByYear($year === DataGrid::OPTION_ALL ? null : (int) ($year ?? ChronosDate::today()->year));
            });

        $grid->addFilterText('search', 'Název', 'name')
            ->setPlaceholder('Hledat podle názvu...');

        $grid->addColumnText('prefix', 'Prefix')
            ->setSortable();

        $grid->setDataSource(new EducationListDataSource($this->queryBus));
        $grid->setDefaultSort(['name' => 'ASC']);

        $grid->setDefaultFilter([
            'year' => (string) ChronosDate::today()->year,
        ]);

        return $grid;
    }
}
