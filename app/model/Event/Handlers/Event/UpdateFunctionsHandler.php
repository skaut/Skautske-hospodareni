<?php

namespace Model\Event\Handlers\Event;

use Model\Event\AssistantNotAdultException;
use Model\Event\Commands\Event\UpdateFunctions;
use Model\Event\LeaderNotAdultException;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;

class UpdateFunctionsHandler
{

    /** @var Skautis */
    private $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    /**
     * @throws AssistantNotAdultException
     * @throws LeaderNotAdultException
     * @throws WsdlException
     */
    public function handle(UpdateFunctions $command): void
    {
        $functions = $command->getFunctions();

        $query = [
            "ID" => $command->getEventId(),
            "ID_PersonLeader" => $functions->getLeaderId(),
            "ID_PersonAssistant" => $functions->getAssistantId(),
            "ID_PersonEconomist" => $functions->getAccountantId(),
            "ID_PersonMedic" => $functions->getMedicId(),
        ];

        try {

            $this->skautis->event->EventGeneralUpdateFunction($query);
        } catch (WsdlException $e) {
            if (strpos($e->getMessage(), 'EventFunction_LeaderMustBeAdult') != FALSE) {
                throw new LeaderNotAdultException();
            }

            if (strpos($e->getMessage(), 'EventFunction_AssistantMustBeAdult') !== FALSE) {
                throw new AssistantNotAdultException();
            }

            throw $e;
        }
    }

}
