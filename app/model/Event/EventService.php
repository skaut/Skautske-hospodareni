<?php

/**
 * @author Hána František
 */
class EventService extends MutableBaseService {
    
    protected static $ID_Functions = array("ID_PersonLeader", "ID_PersonAssistant", "ID_PersonEconomist");

    public function getAll($year = NULL, $state = NULL) {
        $year  = ($year  == "all") ? NULL : $year;
        $state = ($state == "all") ? NULL : $state;
        
        return $this->skautIS->event->{"Event" . self::$typeName . "All"}(array("IsRelation" => TRUE, "ID_Event" . self::$typeName . "State" => $state, "Year" => $year));
    }

    /**
     * vrací detail
     * @param ID_Event $ID
     * @return stdClass 
     */
    public function get($ID) {
        try {            
            $cacheId = __FUNCTION__ . $ID;
            if (!($res = $this->load($cacheId)))
                $res = $this->save($cacheId, $this->skautIS->event->{"Event" . self::$typeName . "Detail"}(array("ID" => $ID)));
            return $res;
        } catch (SkautIS_Exception $e) {
            throw new SkautIS_PermissionException("Nemáte oprávnění pro získání požadovaných informací.", $e->getCode);
        }
    }

    /**
     * vrací obsazení funkcí na zadané akci
     * @param ID_Unit $ID
     * @return type 
     */
    public function getFunctions($ID) {
        return $this->skautIS->event->{"EventFunctionAll" . self::$typeName}(array("ID_Event" . self::$typeName => $ID));
    }
    
    /**
     * vrátí data funkcí připravená pro update
     * @param type $ID_Event
     * @return type
     */
    protected function getPreparedFunctions($ID_Event){
        $data = $this->getFunctions($ID_Event);
        $query = array("ID" => $ID_Event);
        for($i=0;$i<3;$i++){
            $query[self::$ID_Functions[$i]] = $data[$i]->ID_Person;
        }
        return $query;
    }
    
    /**
     * nastaví danou funkci
     * @param type $ID_Event
     * @param type $ID_Person
     * @param type $ID_Function
     * @return type
     */
    public function setFunction($ID_Event, $ID_Person, $ID_Function) {
        $query = $this->getPreparedFunctions($ID_Event);
        $query[self::$ID_Functions[$ID_Function]] = $ID_Person;//nova změna
        return $this->skautIS->event->{self::$typeLongName."UpdateFunction"}($query);
    }

    /**
     * vrací seznam všech stavů akce
     * používá Cache
     * @return array
     */
    public function getStates() {
        $cacheId = __FUNCTION__ . self::$typeName;
        if (!($ret = $this->cache->load($cacheId))) {
            $res = $this->skautIS->event->{"Event" . self::$typeName . "StateAll"}();
            $ret = array();
            foreach ($res as $value) {
                $ret[$value->ID] = $value->DisplayName;
            }
            $this->cache->save($cacheId, $ret, array(Cache::EXPIRE => self::$expire));
        }
        return $ret;
    }

    /**
     * vrací seznam všech rozsahů
     * používá Cache
     * EventGeneral specific
     * @return array
     */
    public function getScopes() {
        $cacheId = __FUNCTION__ . self::$typeName;
        if (!($ret = $this->cache->load($cacheId))) {
            $res = $this->skautIS->event->EventGeneralScopeAll();
            $ret = array();
            foreach ($res as $value) {
                $ret[$value->ID] = $value->DisplayName;
            }
            $this->cache->save($cacheId, $ret, array(Cache::EXPIRE => self::$expire));
        }
        return $ret;
    }

    /**
     * vrací seznam všech typů akce
     * používá Cache
     * @return array
     */
    public function getTypes() {
        $cacheId = __FUNCTION__ . self::$typeName;
        if (!($ret = $this->cache->load($cacheId))) {
            $res = $this->skautIS->event->{self::$typeLongName . "TypeAll"}();
            $ret = array();
            foreach ($res as $value) {
                $ret[$value->ID] = $value->DisplayName;
            }
            $this->cache->save($cacheId, $ret, array(Cache::EXPIRE => self::$expire));
        }
        return $ret;
    }

    /**
     * založí akci ve SkautIS
     * EventGeneral specific
     * @param string $name nazev
     * @param date $start datum zacatku
     * @param date $end datum konce
     * @param ID_Unit $unit ID jednotky
     * @param int $scope  rozsah zaměření akce
     * @param int $type typ akce
     * @return int|stdClass ID akce 
     */
        public function create($name, $start, $end, $location = " ", $unit = NULL, $scope = NULL, $type=NULL) {
        $scope = $scope !== NULL ? $scope : 2; //3-stedisko, 2-oddil
        $type = $type !== NULL ? $type : 2; //2-vyprava
        $unit = $unit !== NULL ? $unit : $this->skautIS->getUnitId();

        $location = !empty($location) && $location != NULL ? $location : " ";

        $ret = $this->skautIS->event->EventGeneralInsert(
                array(
            "ID" => 1, //musi byt neco nastavene
            "Location" => $location, //musi byt neco nastavene
            "Note" => " ", //musi byt neco nastavene
            "ID_EventGeneralScope" => $scope,
            "ID_EventGeneralType" => $type,
            "ID_Unit" => $unit,
            "DisplayName" => $name,
            "StartDate" => $start,
            "EndDate" => $end,
            "IsStatisticAutoComputed" => false,
                ), "eventGeneral");
//
//
//        $this->skautIS->event->EventGeneralUpdateFunction(array(
//            "ID" => $ret->ID,
//            "ID_PersonLeader" => $leader,
//            "ID_PersonAssistant" => $assistant,
//            "ID_PersonEconomist" => $economist
//        ));

        if (isset($ret->ID))
            return $ret->ID;
        return $ret;
    }

    /**
     * aktualizuje informace o akci
     * EventGeneral specific
     * @param array $data
     * @return int
     */
    public function update($data) {

        $ID = $data['aid'];
        $old = $this->get($ID);

        $ret = $this->skautIS->event->EventGeneralUpdate(array(
            "ID" => $ID,
            "Location" => $data['location'],
            "Note" => $old->Note,
            "ID_EventGeneralScope" => $old->ID_EventGeneralScope,
            "ID_EventGeneralType" => $old->ID_EventGeneralType,
            "ID_Unit" => $old->ID_Unit,
            "DisplayName" => $data['name'],
            "StartDate" => $data['start'],
            "EndDate" => $data['end'],
                ), "eventGeneral");

        $this->skautIS->event->EventGeneralUpdateFunction(array(
            "ID" => $ID,
            "ID_PersonLeader" => $data['leader'],
            "ID_PersonAssistant" => $data['assistant'],
            "ID_PersonEconomist" => $data['economist'],
        ));

        if (isset($ret->ID))
            return $ret->ID;
        return $ret;
    }
    
    /**
     * aktivuje dopocitavani kategorii a tim jsou i kategorie pristupne
     * @param type $camp
     * @throws NotImplementedException 
     */
    public function openCampCategories($camp){
        throw new NotImplementedException("zatím nefunguje, čeká na odpoved Jerryho");
        $this->skautIS->event->EventCampUpdate(array(
            "ID"=> $camp->ID,
            "DisplayName"=> $camp->DisplayName,
            "StartDate"=> $camp->StartDate,
            "EndDate"=> $camp->EndDate,
            "GpsLatitude"=> $camp->GpsLatitude,
            "GpsLatitudeText"=> $camp->GpsLatitudeText,
            "GpsLongitude"=> $camp->GpsLongitude,
            "GpsLongitudeText"=> $camp->GpsLongitudeText,
            "IsFloodArea"=> $camp->IsFloodArea,
            "Location"=> $camp->Location,
            "MobileContact"=> $camp->MobileContact,
            "Postcode"=> $camp->Postcode,
            "Region"=> $camp->Region,
            
            "IsRealTotalCostAutoComputed" => TRUE,
            
        ), "eventCamp");
    }

    /**
     * zrušit akci
     * @param int $ID
     * @param string $msg
     * @return type 
     */
    public function cancel($ID, $msg = NULL) {
        $msg = $msg ? $msg : " ";

        $ret = $this->skautIS->event->{"Event" . self::$typeName . "UpdateCancel"}(array(
            "ID" => $ID,
            "CancelDecision" => $msg
                ), "event" . self::$typeName);
        if ($ret) {//smaže paragony
            Environment::getContext()->eventService->chits->deleteAll($ID);
        }
        return $ret;
    }

    /**
     * znovu otevřít
     * @param ID_ $ID
     * @return type 
     */
    public function open($ID) {
        return $this->skautIS->event->{"Event" . self::$typeName . "UpdateOpen"}(
                        array(
                    "ID" => $ID,
                        ), "event" . self::$typeName);
    }

    /**
     * uzavře 
     * @param int $ID - ID akce
     */
    public function close($ID) {
        $this->skautIS->event->{"Event" . self::$typeName . "UpdateClose"}(
                array(
            "ID" => $ID,
                ), "event" . self::$typeName);
    }

    /**
     * kontrolu jestli je možné uzavřít
     * @param int $ID
     * @return bool
     */
    public function isCloseable($ID) {
        $func = $this->getFunctions($ID);
        if ($func[0]->ID_Person != NULL) // musí být nastaven vedoucí akce
            return TRUE;
        return FALSE;
    }

    /**
     * kontroluje jestli lze upravovat
     * @param ID_|stdClass $arg - příjmá bud ID nebo jiz akci samotnou
     * @return bool
     */
    public function isEditable($arg) {
        if (!$arg instanceof stdClass) {
            try {
                $arg = $this->get($arg);
            } catch (SkautIS_PermissionException $exc) {
                return FALSE;
            }
        }
        return $arg->{"ID_Event" . self::$typeName . "State"} == "draft" ? TRUE : FALSE;
    }

}

