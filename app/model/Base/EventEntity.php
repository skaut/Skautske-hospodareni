<?php

namespace Model;
use Dibi\Connection;
use Nette\Caching\IStorage;
use Skautis\Skautis;

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

    public function __construct(string $name, IChitServiceFactory $chitFactory, IParticipantServiceFactory $participantFactory,
                                Skautis $skautIS, IStorage $cacheStorage, Connection $connection)
    {
        $this->event = new EventService($name, $skautIS, $cacheStorage, $connection);
        $this->participants = $participantFactory->create($name);

        $this->chits = $chitFactory->create($name, $this->event);
    }

    public function __get($name) {
        if (isset($this->$name)) {
            return $this->$name;
        }
        throw new \InvalidArgumentException("Invalid service request for: " . $name);
    }

}
