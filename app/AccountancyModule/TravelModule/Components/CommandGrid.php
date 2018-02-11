<?php


namespace App\AccountancyModule\TravelModule\Components;


use App\AccountancyModule\Factories\BaseGridControl;
use Doctrine\Common\Collections\ArrayCollection;
use Model\DTO\Travel\Command;
use Model\TravelService;
use Ublaboo\DataGrid\DataGrid;

class CommandGrid extends BaseGridControl
{

    /** @var int */
    private $unitId;

    /** @var TravelService */
    private $travel;


    public function __construct(int $unitId, TravelService $travel)
    {
        parent::__construct();
        $this->unitId = $unitId;
        $this->travel = $travel;
    }


    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->createGrid();

        $grid->setPrimaryKey("id");

        $grid->addColumnText('purpose', 'Účel cesty')->setSortable()->setFilterText();
        $grid->addColumnText('passenger', 'Cestující')->setSortable()->setFilterText();
        $grid->addColumnText('types', 'Prostředek');
        $grid->addColumnText('vehicle', 'Vozidlo');
        $grid->addColumnDateTime('firstTravelDate', 'První jízda')->setSortable();
        $grid->addColumnNumber('total', 'Cena')->setSortable();
        $grid->addColumnText('note', 'Poznámka');
        $grid->addColumnText('state', 'Stav')
            ->setSortable()
            ->setFilterSelect([
                Command::STATE_IN_PROGRESS => 'Rozpracovaný',
                Command::STATE_CLOSED => 'Uzavřený',
             ])->setPrompt('-');

        $grid->addColumnText('action', '');

        $grid->setPagination(FALSE);

        $grid->setDefaultFilter(['state' => Command::STATE_IN_PROGRESS], FALSE);

        $commands = $this->travel->getAllCommands($this->unitId);

        $grid->setDataSource(new ArrayCollection($commands));

        $commandIds = array_column($commands, 'id');
        $vehicleIds = array_column($commands, 'vehicleId');
        $vehicleIds = array_unique(array_filter($vehicleIds));

        $grid->onRender[] = function(DataGrid $grid) use ($commandIds, $vehicleIds) {
            $grid->template->types = $this->travel->getTypes($commandIds);
            $grid->template->vehicles = $this->travel->findVehiclesByIds($vehicleIds);
            $grid->setTemplateFile(__DIR__ . "/templates/CommandGrid.latte");
        };

        return $grid;
    }

}
