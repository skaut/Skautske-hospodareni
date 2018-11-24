<?php

declare(strict_types=1);

namespace Model\Skautis\Participant;

use Model\Participant\EventType;
use Model\Participant\Participant;
use Model\Participant\Repositories\IParticipantRepository;
use Model\Skautis\Factory\ParticipantFactory;
use Skautis\Wsdl\WebServiceInterface;
use function array_map;
use function ucfirst;

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
    public function findByEvent(EventType $type, int $eventId) : array
    {
        $participants = $this->eventWebservice->call(
            'Participant' . ucfirst($type->toString()) . 'All',
            [['ID_Event' . ucfirst($type->toString()) => $eventId]]
        );

        return array_map([ParticipantFactory::class, 'create'], $participants);
    }
}
