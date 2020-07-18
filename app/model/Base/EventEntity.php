<?php

declare(strict_types=1);

namespace Model;

/**
 * @property-read EventService $event
 * @property-read ParticipantService $participants
 */
class EventEntity
{
    private ParticipantService $participants;

    public function __construct(string $name, IParticipantServiceFactory $participantFactory)
    {
        $this->participants = $participantFactory->create($name);
    }

    public function getParticipants() : ParticipantService
    {
        return $this->participants;
    }
}
