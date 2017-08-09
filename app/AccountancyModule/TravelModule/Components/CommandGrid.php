<?php


namespace App\AccountancyModule\TravelModule\Components;


use App\AccountancyModule\Factories\GridFactory;
use Model\TravelService;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

class CommandGrid extends Control
{

    /** @var int */
    private $unitId;

    /** @var GridFactory */
    private $factory;

    /** @var TravelService */
    private $travel;


    public function __construct(int $unitId, GridFactory $factory, TravelService $travel)
    {
        parent::__construct();
        $this->unitId = $unitId;
        $this->factory = $factory;
        $this->travel = $travel;
    }


    public function render()
    {
        $this['grid']->render();
    }


    protected function createComponentGrid(): DataGrid
    {
        $grid = $this->factory->create();

        $grid->setPrimaryKey("id");

        $grid->addColumnText('driver', 'Cestující')->setSortable()->setFilterText();
        $grid->addColumnText('purpose', 'Účel cesty')->setSortable()->setFilterText();
        $grid->addColumnText('types', 'Prostředek');
        $grid->addColumnText('vehicle', 'Vozidlo');
        $grid->addColumnDateTime('firstTravelDate', 'První jízda')->setSortable();
        $grid->addColumnText('total', 'Cena')->setSortable();
        $grid->addColumnText('note', 'Poznámka');
        $grid->addColumnText('state', 'Stav')->setSortable();
        $grid->addColumnText('action', '');

        $grid->setPagination(FALSE);

        $commands = $this->travel->getAllCommands($this->unitId);

        $grid->setDataSource($commands);

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
