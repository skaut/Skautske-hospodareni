<?php

declare(strict_types=1);

namespace Model\Event\Handlers\Camp;

use Model\Event\Commands\Camp\ActivateAutocomputedCashbook;
use Skautis\Skautis;

class ActivateAutocomputedCashbookHandler
{
    public function __construct(private Skautis $skautis)
    {
    }

    public function __invoke(ActivateAutocomputedCashbook $command): void
    {
        $this->skautis->event->eventCampUpdateRealTotalCostBeforeEnd([
            'ID' => $command->getCampId()->toInt(),
            'IsRealTotalCostAutoComputed' => 1,
            'IsOnlineLogin' => false,
        ], 'eventCamp');
    }
}
