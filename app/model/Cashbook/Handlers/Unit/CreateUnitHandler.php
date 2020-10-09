<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Unit;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Unit\CreateUnit;
use Model\Cashbook\Repositories\IUnitRepository;
use Model\Cashbook\Unit;

final class CreateUnitHandler
{
    private IUnitRepository $units;

    public function __construct(IUnitRepository $units)
    {
        $this->units = $units;
    }

    public function __invoke(CreateUnit $command) : void
    {
        $this->units->save(new Unit($command->getUnitId(), CashbookId::generate(), $command->getActiveCashbookYear()));
    }
}
