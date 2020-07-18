<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Components;

use App\AccountancyModule\Factories\BaseGridControl;
use App\AccountancyModule\Factories\GridFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Model\DTO\Travel\Command;
use Model\TravelService;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\DataSource\DoctrineCollectionDataSource;
use function array_column;
use function array_filter;
use function array_unique;

class CommandGrid extends BaseGridControl
{
    private int $unitId;

    private int $userId;

    private TravelService $travel;

    private GridFactory $gridFactory;

    public function __construct(
        int $unitId,
        int $userId,
        TravelService $travel,
        GridFactory $gridFactory
    ) {
        parent::__construct();
        $this->unitId      = $unitId;
        $this->userId      = $userId;
        $this->travel      = $travel;
        $this->gridFactory = $gridFactory;
    }

    protected function createComponentGrid() : DataGrid
    {
        $commands = $this->travel->getAllUserCommands($this->unitId, $this->userId);

        $vehicleIds = array_column($commands, 'vehicleId');
        $vehicleIds = array_unique(array_filter($vehicleIds));

        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__ . '/templates/CommandGrid.latte',
            ['vehicles' => $this->travel->findVehiclesByIds($vehicleIds)]
        );

        $grid->setPrimaryKey('id');

        $grid->addColumnLink('purpose', 'Účel cesty', 'Default:detail')
            ->setSortable()
            ->setFilterText();

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

        $commands = $this->travel->getAllUserCommands($this->unitId, $this->userId);

        $grid->setDataSource(new DoctrineCollectionDataSource(new ArrayCollection($commands), 'id'));

        return $grid;
    }
}
