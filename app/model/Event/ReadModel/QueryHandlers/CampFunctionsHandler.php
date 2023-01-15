<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Functions;
use Model\Event\Person;
use Model\Event\ReadModel\PersonFactory;
use Model\Event\ReadModel\Queries\CampFunctions;
use Skautis\Skautis;
use stdClass;

class CampFunctionsHandler
{
    public function __construct(private Skautis $skautis)
    {
    }

    public function __invoke(CampFunctions $query): Functions
    {
        $functions = $this->skautis->event->eventFunctionAllCamp([
            'ID_EventCamp' => $query->getCampId()->toInt(),
        ]);

        $functionsByType = $this->getFunctionsByType($functions);

        return new Functions(
            $functionsByType['leader'],
            $functionsByType['assistant'],
            $functionsByType['economist'],
            $functionsByType['medic'],
        );
    }

    /**
     * @param stdClass[] $functions
     *
     * @return Person[]
     */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    private function getFunctionsByType(array $functions): array
    {
        $functionsByType = [];

        foreach ($functions as $function) {
            $functionsByType[$function->EventFunctionTypeKey] = PersonFactory::create($function);
        }

        return $functionsByType;
    }
}
