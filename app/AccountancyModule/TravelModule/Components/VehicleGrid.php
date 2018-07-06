<?php

namespace App\AccountancyModule\TravelModule\Components;

use App\AccountancyModule\Factories\BaseGridControl;
use Doctrine\Common\Collections\ArrayCollection;
use Model\TravelService;
use Model\UnitService;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\DataSource\DoctrineCollectionDataSource;

class VehicleGrid extends BaseGridControl
{

    /** @var int */
    private $unitId;

    /** @var TravelService */
    private $travel;

    /** @var UnitService */
    private $units;


    public function __construct(int $unitId, TravelService $travel, UnitService $units)
    {
        parent::__construct();
        $this->unitId = $unitId;
        $this->travel = $travel;
        $this->units = $units;
    }


    protected function createComponentGrid() : DataGrid
    {
        $grid = $this->createGrid();
        $grid->addColumnText('type', 'Typ');
        $grid->addColumnText('registration', 'SPZ');
        $grid->addColumnText('consumption', '	Ø spotřeba (l/100 km)');

        $units = $this->units->getSubunitPairs($this->unitId);

        $grid->addColumnText('subunit', 'Oddíl')
            ->setFilterSelect($units, 'subunitId')->setPrompt('-');

        $grid->addColumnDateTime('createdAt', 'Vytvořeno')
            ->setSortable(TRUE);

        $grid->addColumnDateTime('authorName', 'Vytvořil')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('action', '');
        $grid->setPagination(FALSE);

        $vehicles = $this->travel->getAllVehicles($this->unitId);
        $grid->setDataSource(new DoctrineCollectionDataSource(new ArrayCollection($vehicles), 'id'));

        $grid->onRender[] = function(DataGrid $grid) use ($units) {
            $grid->template->units = $units;
            $grid->setTemplateFile(__DIR__ . '/templates/VehicleGrid.latte');
        };

        return $grid;
    }

}
