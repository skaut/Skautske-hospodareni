<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Unit;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Commands\Unit\CreateUnit;
use App\Model\Cashbook\Repositories\IUnitRepository;
use App\Model\Cashbook\Unit;

final class CreateUnitHandler
{
    public function __construct(private IUnitRepository $units)
    {
    }

    public function __invoke(CreateUnit $command): void
    {
        $this->units->save(new Unit($command->getUnitId(), CashbookId::generate(), $command->getActiveCashbookYear()));
    }
}
