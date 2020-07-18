<?php

declare(strict_types=1);

namespace Model\Event\Handlers\Event;

use Model\Event\AssistantNotAdult;
use Model\Event\Commands\Event\UpdateFunctions;
use Model\Event\LeaderNotAdult;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;
use function strpos;

class UpdateFunctionsHandler
{
    private Skautis $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    /**
     * @throws AssistantNotAdult
     * @throws LeaderNotAdult
     * @throws WsdlException
     */
    public function __invoke(UpdateFunctions $command) : void
    {
        $query = [
            'ID' => $command->getEventId(),
            'ID_PersonLeader' => $command->getLeaderId(),
            'ID_PersonAssistant' => $command->getAssistantId(),
            'ID_PersonEconomist' => $command->getAccountantId(),
            'ID_PersonMedic' => $command->getMedicId(),
        ];

        try {
            $this->skautis->event->EventGeneralUpdateFunction($query);
        } catch (WsdlException $e) {
            if (strpos($e->getMessage(), 'EventFunction_LeaderMustBeAdult') !== false) {
                throw new LeaderNotAdult();
            }

            if (strpos($e->getMessage(), 'EventFunction_AssistantMustBeAdult') !== false) {
                throw new AssistantNotAdult();
            }

            throw $e;
        }
    }
}
