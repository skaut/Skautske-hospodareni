<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Unit;

use Model\Cashbook\Commands\Unit\ActivateCashbook;
use Model\Cashbook\Repositories\IUnitRepository;

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
