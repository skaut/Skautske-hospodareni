<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use App\AccountancyModule\Components\DataGrid;
use App\AccountancyModule\EventModule\EducationListDataSource;
use App\AccountancyModule\Factories\GridFactory;
use Cake\Chronos\Date;

class DefaultPresenter extends BasePresenter
{
    private GridFactory $gridFactory;

    public function __construct(GridFactory $gridFactory)
    {
        parent::__construct();
        $this->gridFactory = $gridFactory;
    }

    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__ . '/../templates/Default/@eventsGrid.latte',
            []
        );

        $grid->addColumnLink('displayName', 'Název', 'Education:', null, ['aid' => 'id'])
            ->setSortable();

        $grid->addColumnDateTime('startDate', 'Začátek akce')
            ->setSortable();

        $grid->addColumnDateTime('endDate', 'Konec akce')
            ->setSortable();

        $grid->addYearFilter('year', 'Rok')
            ->setCondition(function (EducationListDataSource $dataSource, $year): void {
                $dataSource->filterByYear($year === DataGrid::OPTION_ALL ? null : (int) ($year ?? Date::today()->year));
            });

        $grid->setDataSource(new EducationListDataSource($this->queryBus));
        $grid->setDefaultSort(['name' => 'ASC']);

        $grid->setDefaultFilter([
            'year' => (string) Date::today()->year,
        ]);

        return $grid;
    }
}
