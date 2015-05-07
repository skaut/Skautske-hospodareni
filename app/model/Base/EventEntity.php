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

    public function __construct($name, $skautIS, $cacheStorage, $connection) {
        $this->event = new EventService($name, $skautIS, $cacheStorage, $connection);
        $this->participants = new ParticipantService($name, $skautIS, $cacheStorage, $connection);
        $this->chits = new ChitService($name, $skautIS, $cacheStorage, $connection, $this->event);
    }

    public function __get($name) {
        if (isset($this->$name)) {
            return $this->$name;
        }
        throw new \InvalidArgumentException("Invalid service request for: " . $name);
    }

}
