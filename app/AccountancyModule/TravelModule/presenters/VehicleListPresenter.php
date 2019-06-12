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
    }

    protected function createComponentGrid() : VehicleGrid
    {
        return $this->gridFactory->create($this->getUnitId());
    }
}
