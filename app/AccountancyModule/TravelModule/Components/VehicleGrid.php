<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Components;

use App\AccountancyModule\Factories\BaseGridControl;
use App\AccountancyModule\Factories\GridFactory;
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

    /** @var GridFactory */
    private $gridFactory;

    public function __construct(int $unitId, TravelService $travel, UnitService $units, GridFactory $gridFactory)
    {
        parent::__construct();
        $this->unitId      = $unitId;
        $this->travel      = $travel;
        $this->units       = $units;
        $this->gridFactory = $gridFactory;
    }

    protected function createComponentGrid() : DataGrid
    {
        $units = $this->units->getSubunitPairs($this->unitId);

        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__ . '/templates/VehicleGrid.latte',
            ['units' => $units],
        );

        $grid->addColumnLink('type', 'Typ', 'Vehicle:detail')
            ->setSortable();

        $grid->addColumnText('registration', 'SPZ');
        $grid->addColumnText('consumption', 'Ø spotřeba (l/100 km)');

        $grid->addColumnText('subunit', 'Oddíl')
            ->setFilterSelect($units, 'subunitId')->setPrompt('Všechny');

        $grid->addColumnDateTime('createdAt', 'Vytvořeno')
            ->setSortable();

        $grid->addColumnDateTime('authorName', 'Vytvořil')
            ->setSortable()
            ->setFilterText();

        $grid->addFilterText('search', '', ['type', 'authorName'])
            ->setPlaceholder('Typ vozdila, uživatel...');

        $vehicles = $this->travel->getAllVehicles($this->unitId);
        $grid->setDataSource(new DoctrineCollectionDataSource(new ArrayCollection($vehicles), 'id'));

        return $grid;
    }
}
