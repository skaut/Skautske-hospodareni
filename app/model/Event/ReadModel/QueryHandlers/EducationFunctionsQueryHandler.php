<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Functions;
use Model\Event\ReadModel\PersonFactory;
use Model\Event\ReadModel\Queries\EducationFunctions;
use Skautis\Skautis;

use function array_map;

class EducationFunctionsQueryHandler
{
    public function __construct(private Skautis $skautis)
    {
    }

    public function __invoke(EducationFunctions $query): Functions
    {
        $functions = $this->skautis->event->eventFunctionAllEducationLeader([
            'ID_EventEducation' => $query->getEducationId()->toInt(),
        ]);

        return new Functions(
            ...array_map([PersonFactory::class, 'create'], $functions),
        );
    }
}
