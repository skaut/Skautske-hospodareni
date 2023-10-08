<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\EducationFunctions as EFunctions;
use Model\Event\ReadModel\PersonFactory;
use Model\Event\ReadModel\Queries\EducationFunctions;
use Skautis\Skautis;
use stdClass;

use function array_map;

class EducationFunctionsQueryHandler
{
    public function __construct(private Skautis $skautis)
    {
    }

    public function __invoke(EducationFunctions $query): EFunctions
    {
        $functions = $this->skautis->event->eventFunctionAllEducationLeader([
            'ID_EventEducation' => $query->getEducationId()->toInt(),
        ]);

        $assistants = $this->skautis->event->eventFunctionAllEducationAssistant([
            'ID_EventEducation' => $query->getEducationId()->toInt(),
        ]);

        if ($assistants instanceof stdClass) {
            $assistants = [];
        }

        return new EFunctions(
            PersonFactory::create($functions[0]),
            PersonFactory::create($functions[1]),
            PersonFactory::create($functions[2]),
            PersonFactory::create($functions[3]),
            array_map([PersonFactory::class, 'create'], $assistants),
        );
    }
}
