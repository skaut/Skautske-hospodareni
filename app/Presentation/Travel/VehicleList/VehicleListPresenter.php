<?php

declare(strict_types=1);

namespace App\Presentation\Travel\VehicleList;

use App\Presentation\Travel\Accessory\Components\VehicleGrid;
use App\Presentation\Travel\Accessory\Factories\IVehicleGridFactory;
use App\Presentation\Travel\TravelBasePresenter;

class VehicleListPresenter extends TravelBasePresenter
{
    public function __construct(private IVehicleGridFactory $gridFactory)
    {
        parent::__construct();
    }

    protected function createComponentGrid(): VehicleGrid
    {
        return $this->gridFactory->create($this->getUnitId());
    }
}
