<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers\Unit;

use Model\Cashbook\Commands\Unit\CreateCashbook;
use Model\Cashbook\Repositories\IUnitRepository;

final class CreateCashbookHandler
{
    /** @var IUnitRepository */
    private $units;

    public function __construct(IUnitRepository $units)
    {
        $this->units = $units;
    }

    public function __invoke(CreateCashbook $command) : void
    {
        $unit = $this->units->find($command->getUnitId());

        $unit->createCashbook($command->getYear());

        $this->units->save($unit);
    }
}
