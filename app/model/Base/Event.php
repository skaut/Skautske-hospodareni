<?php

/**
 *
 * @author sinacek
 */
class Event {
    private $event;
    private $participants;
    
    public function __construct($skautIS, $cacheStorage) {
        $this->event        = new EventService( "General", "EventGeneral", "+ 2 days", $skautIS, $cacheStorage);
        $this->participants = new ParticipantService( "General", "EventGeneral", "+ 2 days", $skautIS, $cacheStorage);
    }
    
    public function __get($name) {
        if( isset($this->$name) ){
            return $this->$name;
        }
        throw new InvalidArgumentException("Neplatný požazdavek na ".$name);
    }
}
