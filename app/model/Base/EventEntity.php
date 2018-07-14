<?php

declare(strict_types=1);

namespace Model;

/**
 * @property-read EventService $event
 * @property-read ParticipantService $participants
 */
class EventEntity
{
    /** @var EventService */
    private $event;

    /** @var ParticipantService */
    private $participants;

    public function __construct(
        string $name,
        IParticipantServiceFactory $participantFactory,
        IEventServiceFactory $eventFactory
    ) {
        $this->event        = $eventFactory->create($name);
        $this->participants = $participantFactory->create($name);
    }

    /**
     * @deprecated Use getXxxx()
     * @return mixed
     */
    public function __get(string $name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        throw new \InvalidArgumentException('Invalid service request for: ' . $name);
    }

    public function getEvent() : EventService
    {
        return $this->event;
    }

    public function getParticipants() : ParticipantService
    {
        return $this->participants;
    }
}
