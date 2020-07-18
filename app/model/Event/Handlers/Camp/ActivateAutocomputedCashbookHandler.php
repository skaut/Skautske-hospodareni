<?php

declare(strict_types=1);

namespace Model\Event\Handlers\Camp;

use Model\Event\Commands\Camp\ActivateAutocomputedCashbook;
use Skautis\Skautis;

class ActivateAutocomputedCashbookHandler
{
    private Skautis $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    public function __invoke(ActivateAutocomputedCashbook $command) : void
    {
        $this->skautis->event->eventCampUpdateRealTotalCostBeforeEnd([
            'ID' => $command->getCampId()->toInt(),
            'IsRealTotalCostAutoComputed' => 1,
        ], 'eventCamp');
    }
}
