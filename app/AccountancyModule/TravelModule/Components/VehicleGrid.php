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
    public function __construct(private int $unitId, private TravelService $travel, private UnitService $units, private GridFactory $gridFactory)
    {
    }

    protected function createComponentGrid(): DataGrid
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
