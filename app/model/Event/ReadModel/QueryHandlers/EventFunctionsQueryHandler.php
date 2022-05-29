<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Functions;
use Model\Event\ReadModel\PersonFactory;
use Model\Event\ReadModel\Queries\EventFunctions;
use Skautis\Skautis;

use function array_map;

class EventFunctionsQueryHandler
{
    private Skautis $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    public function __invoke(EventFunctions $query): Functions
    {
        $functions = $this->skautis->event->eventFunctionAllGeneral([
            'ID_EventGeneral' => $query->getEventId()->toInt(),
        ]);

        return new Functions(
            ...array_map([PersonFactory::class, 'create'], $functions)
        );
    }
}
