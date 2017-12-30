<?php

namespace Model\Event\ReadModel\QueryHandlers;

use Model\Event\Functions;
use Model\Event\Person;
use Model\Event\ReadModel\Queries\EventFunctions;
use Skautis\Skautis;

class EventFunctionsHandler
{

    /** @var Skautis */
    private $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    public function handle(EventFunctions $query): Functions
    {
        $functions = $this->skautis->event->eventFunctionAllGeneral([
            'ID_EventGeneral' => $query->getEventId()->getValue(),
        ]);

        return new Functions(
            ...array_map([self::class, 'buildPerson'], $functions)
        );

    }

    public static function buildPerson(\stdClass $function): ?Person
    {
        if($function->ID_Person === NULL) {
            return NULL;
        }

        return new Person(
            $function->ID_Person,
            $function->Person
        );
    }

}
