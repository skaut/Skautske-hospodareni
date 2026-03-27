<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Unit;

use App\Model\Cashbook\Commands\Unit\ActivateCashbook;
use App\Model\Cashbook\Repositories\IUnitRepository;

final class ActivateCashbookHandler
{
    public function __construct(private IUnitRepository $units)
    {
    }

    public function __invoke(ActivateCashbook $command): void
    {
        $unit = $this->units->find($command->getUnitId());

        $unit->activateCashbook($command->getCashbookId());

        $this->units->save($unit);
    }
}
