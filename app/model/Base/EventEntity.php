<?php

namespace Model;

/**
 *
 * @author Hána František <sinacek@gmail.com>
 */
class EventEntity {

    /** @var EventService */
    private $event;

    /** @var ParticipantService */
    private $participants;

    /** @var ChitService */
    private $chits;

    public function __construct(
        string $name,
        IChitServiceFactory $chitFactory,
        IParticipantServiceFactory $participantFactory,
        IEventServiceFactory $eventFactory)
    {
        $this->event = $eventFactory->create($name);
        $this->participants = $participantFactory->create($name);
        $this->chits = $chitFactory->create($name);
    }

    public function __get($name) {
        if (isset($this->$name)) {
            return $this->$name;
        }
        throw new \InvalidArgumentException("Invalid service request for: " . $name);
    }

}
