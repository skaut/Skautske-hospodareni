<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Functions;
use Model\Event\Person;
use Model\Event\ReadModel\PersonFactory;
use Model\Event\ReadModel\Queries\CampFunctions;
use Skautis\Skautis;

class CampFunctionsHandler
{
    /** @var Skautis */
    private $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    public function handle(CampFunctions $query) : Functions
    {
        $functions = $this->skautis->event->eventFunctionAllCamp([
            'ID_EventCamp' => $query->getCampId()->toInt(),
        ]);

        $functionsByType = $this->getFunctionsByType($functions);

        return new Functions(
            $functionsByType['leader'],
            $functionsByType['assistant'],
            $functionsByType['economist'],
            $functionsByType['medic']
        );
    }

    /**
     * @param \stdClass[] $functions
     * @return Person[]
     */
    private function getFunctionsByType(array $functions) : array
    {
        $functionsByType = [];

        foreach ($functions as $function) {
            $functionsByType[$function->EventFunctionTypeKey] = PersonFactory::create($function);
        }

        return $functionsByType;
    }
}
