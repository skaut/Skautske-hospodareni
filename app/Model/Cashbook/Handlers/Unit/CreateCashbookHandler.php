<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers\Unit;

use App\Model\Cashbook\Commands\Unit\CreateCashbook;
use App\Model\Cashbook\Repositories\IUnitRepository;

final class CreateCashbookHandler
{
    public function __construct(private IUnitRepository $units)
    {
    }

    public function __invoke(CreateCashbook $command): void
    {
        $unit = $this->units->find($command->getUnitId());

        $unit->createCashbook($command->getYear());

        $this->units->save($unit);
    }
}
