<?php

declare(strict_types=1);

namespace App\Components\Travel;

use App\Components\Grids\BaseGridControl;
use App\Components\Grids\GridFactory;
use App\Model\DTO\Travel\Command;
use App\Model\Travel\TravelService;
use Contributte\Datagrid\Datagrid;
use Contributte\Datagrid\DataSource\DoctrineCollectionDataSource;
use Doctrine\Common\Collections\ArrayCollection;

use function array_column;
use function array_filter;
use function array_unique;

class CommandGrid extends BaseGridControl
{
    /** @param int[] $readableUnitIds */
    public function __construct(
        private array $readableUnitIds,
        private int $userId,
        private TravelService $travel,
        private GridFactory $gridFactory,
    ) {
    }

    protected function createComponentGrid(): Datagrid
    {
        $commands = $this->travel->getVisibleUserCommands($this->readableUnitIds, $this->userId);

        $vehicleIds = array_column($commands, 'vehicleId');
        $vehicleIds = array_unique(array_filter($vehicleIds));

        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__.'/templates/CommandGrid.latte',
            ['vehicles' => $this->travel->findVehiclesByIds($vehicleIds)],
        );

        $grid->setPrimaryKey('id');

        $grid->addColumnLink('purpose', 'Účel cesty', 'Default:detail')
            ->setSortable()
            ->setFilterText();

        $grid->addColumnText('unit', 'Jednotka')->setSortable();
        $grid->addColumnText('passenger', 'Cestující')->setSortable();
        $grid->addColumnText('vehicle', 'Vozidlo');
        $grid->addColumnDateTime('firstTravelDate', 'První jízda')->setSortable();
        $grid->addColumnNumber('total', 'Cena')->setSortable();
        $grid->addColumnText('note', 'Poznámka');
        $grid->addColumnText('state', 'Stav')
            ->setSortable()
            ->setFilterSelect([
                Command::STATE_IN_PROGRESS => 'Rozpracovaný',
                Command::STATE_CLOSED => 'Uzavřený',
            ])->setPrompt('Všechny');

        $grid->addFilterText('search', '', ['purpose', 'passenger'])
            ->setPlaceholder('Účel cesty, cestující...');

        $grid->setDefaultFilter(['state' => Command::STATE_IN_PROGRESS], false);

        $grid->setDataSource(new DoctrineCollectionDataSource(new ArrayCollection($commands), 'id'));

        return $grid;
    }
}
