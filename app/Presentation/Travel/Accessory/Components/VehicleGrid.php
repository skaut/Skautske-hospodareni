<?php

declare(strict_types=1);

namespace App\Presentation\Travel\Accessory\Components;

use App\Components\Grids\BaseGridControl;
use App\Components\Grids\GridFactory;
use App\Model\Travel\TravelService;
use App\Model\Travel\VehicleLinkedRecord;
use App\Model\Travel\VehicleNotFound;
use App\Model\Unit\UnitService;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;

class VehicleGrid extends BaseGridControl
{
    public function __construct(private int $unitId, private TravelService $travel, private UnitService $units, private GridFactory $gridFactory)
    {
    }

    protected function createComponentGrid(): DataGrid
    {
        $units = $this->units->getSubunitPairs($this->unitId);

        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__.'/templates/VehicleGrid.latte',
            ['units' => $units],
        );

        $grid->addColumnText('type', 'Typ')
            ->setSortable();

        $grid->addColumnLink('registration', 'SPZ', ':Travel:Vehicle:detail')
            ->setFilterText();
        $grid->addColumnText('consumption', 'Ø spotřeba (l/100 km)');

        $grid->addColumnText('subunit', 'Oddíl')
            ->setFilterSelect($units, 'subunitId')->setPrompt('Všechny');

        $grid->addColumnDateTime('metadata.createdAt', 'Vytvořeno')
            ->setSortable();

        $grid->addColumnDateTime('metadata.authorName', 'Vytvořil')
            ->setSortable();

        $grid->addFilterText('search', '', ['type', 'v.metadata.authorName', 'registration'])
            ->setPlaceholder('Typ vozdila, uživatel...');

        $grid->setDataSource($this->travel->getVehiclesByFilter());

        $grid->addAction('edit', '', ':Travel:Vehicle:detail', ['id' => 'id'])
            ->setIcon('far fa-edit')
            ->setTitle('Detail vozidla')
            ->setClass('btn btn-sm btn-secondary');

        $grid->addAction('delete', '', 'remove!', ['id' => 'id'])
            ->setIcon('far fa-trash-can')
            ->setTitle('Smazat vozidlo')
            ->setClass('btn btn-sm btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu chceš smazat řádek %s?', 'registration'),
            );

        return $grid;
    }

    public function handleRemove(int $id): void
    {
        try {
            $this->travel->removeVehicle($id);
            $this->flashMessage('Vozidlo bylo odebráno.');
        } catch (VehicleLinkedRecord) {
            $this->flashMessage('Nelze smazat vozidlo s cestovními příkazy.', 'warning');
        } catch (VehicleNotFound) {
            $this->flashMessage('Vozidlo nebylo nalezeno', 'warning');
        }

        $this->redirect(':Travel:VehicleList:default');
    }
}
