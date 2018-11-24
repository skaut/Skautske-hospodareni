<?php

declare(strict_types=1);

namespace Model\Skautis\Participant;

use Model\Participant\Event;
use Model\Participant\Participant;
use Model\Participant\Repositories\IParticipantRepository;
use Model\Skautis\Factory\ParticipantFactory;
use Skautis\Wsdl\WebServiceInterface;
use function array_map;

final class SkautisParticipantRepository implements IParticipantRepository
{
    /** @var WebServiceInterface */
    private $eventWebservice;

    public function __construct(WebServiceInterface $eventWebservice)
    {
        $this->eventWebservice = $eventWebservice;
    }

    /**
     * @return Participant[]
     */
    public function findByEvent(Event $event) : array
    {
        $participants = $this->eventWebservice->call(
            'Participant' . $event->getType() . 'All',
            [['ID_Event' . $event->getType() => $event->getId()]]
        );

        return array_map([ParticipantFactory::class, 'create'], $participants);
    }
}
