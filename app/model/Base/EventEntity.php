<?php

/**
 *
 * @author sinacek
 */
class EventEntity{
    
    /** @var EventService*/
    private $event;
    
    /** @var ParticipantService*/
    private $participants;
    
    /** @var ChitService*/
    private $chits;
    
    public function __construct($name, $longName, $expire, $skautIS, $cacheStorage) {
        $this->event        = new EventService($name, $longName, $expire, $skautIS, $cacheStorage);
        $this->participants = new ParticipantService($name, $longName, $expire, $skautIS, $cacheStorage);
        $this->chits        = new ChitService($name, $longName, $expire, $skautIS, $cacheStorage);
    }
    
    public function __get($name) {
        if( isset($this->$name) ){
            return $this->$name;
        }
        throw new InvalidArgumentException("Neplatný požazdavek na ".$name);
    }
}
