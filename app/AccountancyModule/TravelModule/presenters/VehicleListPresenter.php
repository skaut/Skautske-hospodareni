<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule;

use App\AccountancyModule\TravelModule\Components\VehicleGrid;
use App\AccountancyModule\TravelModule\Factories\IVehicleGridFactory;

final class VehicleListPresenter extends BasePresenter
{
    /** @var IVehicleGridFactory */
    private $gridFactory;

    public function __construct(IVehicleGridFactory $gridFactory)
    {
        parent::__construct();
        $this->gridFactory = $gridFactory;
        $this->setLayout('layout.new');
    }

    protected function startup() : void
    {
        parent::startup();
    }

    protected function createComponentGrid() : VehicleGrid
    {
        return $this->gridFactory->create($this->getUnitId());
    }
}
