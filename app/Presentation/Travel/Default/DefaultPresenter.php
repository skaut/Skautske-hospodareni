<?php

declare(strict_types=1);

namespace App\Presentation\Travel\Default;

use App\Components\Grids\GridFactory;
use App\Model\DTO\Travel\Command;
use App\Model\Travel\TravelService;
use Doctrine\Common\Collections\ArrayCollection;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\DataSource\DoctrineCollectionDataSource;

use function array_column;
use function array_filter;
use function array_unique;

final class DefaultPresenter extends \App\BasePresenter
{
    public function __construct(
        private readonly TravelService $travelService,
        private readonly GridFactory $gridFactory,
    ) {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();

        $this->template->setParameters([
            'unit' => $this->unitService->getOfficialUnit(),
        ]);
    }

    protected function createComponentGrid(): DataGrid
    {
        $commands = $this->travelService->getAllUserCommands($this->getUnitId(), $this->getUser()->getId());
        $vehicleIds = array_unique(array_filter(array_column($commands, 'vehicleId')));

        $grid = $this->gridFactory->createSimpleGrid(
            __DIR__.'/CommandGrid.latte',
            ['vehicles' => $this->travelService->findVehiclesByIds($vehicleIds)],
        );

        $grid->setPrimaryKey('id');

        $grid->addColumnLink('purpose', 'Účel cesty', ':Travel:Command:detail')
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

        $grid->addAction('detail', '', ':Travel:Command:detail')
            ->setIcon('fi fi-rr-search')
            ->setTitle('Detail')
            ->setClass('btn btn-sm btn-primary');

        return $grid;
    }
}
