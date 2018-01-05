<?php

namespace Model\Event\Handlers\Event;

use Model\Event\Commands\Event\CreateEvent;
use Skautis\Skautis;

final class CreateEventHandler
{

    /** @var Skautis */
    private $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    public function handle(CreateEvent $command): void
    {
        $query = [
            "ID" => 1, // musi byt neco nastavene
            "Location" => $command->getLocation() ?? ' ',
            "Note" => " ", // musi byt neco nastavene
            "ID_EventGeneralScope" => $command->getScopeId(),
            "ID_EventGeneralType" => $command->getTypeId(),
            "ID_Unit" => $command->getUnitId(),
            "DisplayName" => $command->getName(),
            "StartDate" => $command->getStartDate()->format('Y-m-d'),
            "EndDate" => $command->getEndDate()->format('Y-m-d'),
            "IsStatisticAutoComputed" => FALSE,
        ];

        $this->skautis->event->EventGeneralInsert($query, "eventGeneral");
    }


}
