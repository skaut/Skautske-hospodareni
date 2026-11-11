<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers;

use App\Model\Event\EducationFunctions as EFunctions;
use App\Model\Event\ReadModel\PersonFactory;
use App\Model\Event\ReadModel\Queries\EducationFunctions;
use Skaut\Skautis\Skautis;

use function array_map;
use function is_array;

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

        if (! is_array($assistants)) {
            $assistants = [];
        }

        return new EFunctions(
            PersonFactory::create($functions[0]),
            PersonFactory::create($functions[1]),
            PersonFactory::create($functions[2]),
            PersonFactory::create($functions[3]),
            array_filter(array_map([PersonFactory::class, 'create'], $assistants)),
        );
    }
}
