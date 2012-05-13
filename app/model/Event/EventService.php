<?php

/**
 * @author Hána František
 */
class EventService extends BaseService {
    const LEADER = 0; //ID v poli funkcí
    const ASSISTANT = 1; //ID v poli funkcí
    const ECONOMIST = 2; //ID v poli funkcí

    /** @var FileStorage */
    protected $cache;

    public function __construct($skautIS, $cacheStorage) {
        parent::__construct($skautIS);
        $cache = new Cache($cacheStorage, __CLASS__);
        $this->cache = $cache;
    }

    public function getAll($year = NULL, $state = NULL) {
        return $this->skautIS->event->EventGeneralAll(array("IsRelation" => TRUE, "ID_EventGeneralState" => $state, "Year" => $year));
    }

    /**
     * vrací detail vybraé akce
     * @param ID_Event $eventId
     * @return stdClass 
     */
    public function get($eventId) {
        try {
            $id = __FUNCTION__ . $eventId;
            if (!($res = $this->load($id)))
                $res = $this->save($id, $this->skautIS->event->EventGeneralDetail(array("ID" => $eventId)) );
            return $res;
        } catch (SkautIS_Exception $e) {
            throw new SkautIS_PermissionException("Nemáte oprávnění pro získání informací o akci.", $e->getCode);
        }
    }

    /**
     * vrací obsazení funkcí na zadané akci
     * @param ID_Unit $eventId
     * @return type 
     */
    public function getFunctions($eventId) {
        return $this->skautIS->event->EventFunctionAllGeneral(array("ID_EventGeneral" => $eventId));
    }

    /**
     * vrací seznam všech stavů akce
     * používá Cache
     * @return array
     */
    public function getStates() {
        if (!($ret = $this->cache->load(__FUNCTION__))) {
            $res = $this->skautIS->event->EventGeneralStateAll();
            $ret = array();
            foreach ($res as $value) {
                $ret[$value->ID] = $value->DisplayName;
            }
            $this->cache->save(__FUNCTION__, $ret, array(Cache::EXPIRE => '+ 1 days'));
        }
        return $ret;
    }

    /**
     * vrací seznam všech rozsahů akce
     * používá Cache
     * @return array
     */
    public function getScopes() {
        if (!($ret = $this->cache->load(__FUNCTION__))) {
            $res = $this->skautIS->event->EventGeneralScopeAll();
            $ret = array();
            foreach ($res as $value) {
                $ret[$value->ID] = $value->DisplayName;
            }
            $this->cache->save(__FUNCTION__, $ret, array(Cache::EXPIRE => '+ 1 days') );
        }
        return $ret;
    }

    /**
     * vrací seznam všech typů akce
     * používá Cache
     * @return array
     */
    public function getTypes() {
        if (!($ret = $this->cache->load(__FUNCTION__))) {
            $res = $this->skautIS->event->EventGeneralTypeAll();
            $ret = array();
            foreach ($res as $value) {
                $ret[$value->ID] = $value->DisplayName;
            }
            $this->cache->save(__FUNCTION__, $ret, array(Cache::EXPIRE => '+ 1 days'));
        }
        return $ret;
    }

    /**
     * založí akci ve SkautIS
     * @param string $name nazev
     * @param date $start datum zacatku
     * @param date $end datum konce
     * @param ID_Person $leader
     * @param ID_Person $assistant
     * @param ID_Person $economist
     * @param ID_Unit $unit ID jednotky
     * @param int $scope  rozsah zaměření akce
     * @param int $type typ akce
     * @return int|stdClass ID akce 
     */
    public function create($name, $start, $end, $location = " ", $leader = NULL, $assistant = NULL, $economist = NULL, $unit = NULL, $scope = NULL, $type=NULL) {
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


        $this->skautIS->event->EventGeneralUpdateFunction(array(
            "ID" => $ret->ID,
            "ID_PersonLeader" => $leader,
            "ID_PersonAssistant" => $assistant,
            "ID_PersonEconomist" => $economist
        ));

        if (isset($ret->ID))
            return $ret->ID;
        return $ret;
    }

    /**
     * aktualizuje informace o akci
     * @param array $data
     * @return int
     */
    public function update($data) {

        $id = $data['aid'];
        $old = $this->get($id);

        $ret = $this->skautIS->event->EventGeneralUpdate(array(
            "ID" => $id,
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
            "ID" => $id,
            "ID_PersonLeader" => $data['leader'],
            "ID_PersonAssistant" => $data['assistant'],
            "ID_PersonEconomist" => $data['economist'],
        ));

        if (isset($ret->ID))
            return $ret->ID;
        return $ret;
    }

    /**
     * zrušit akci
     * @param int $id
     * @param string $msg
     * @return type 
     */
    public function cancel($id, $msg = NULL) {
        $msg = $msg ? $msg : " ";

        $ret = $this->skautIS->event->EventGeneralUpdateCancel(
                array(
            "ID" => $id,
            "CancelDecision" => $msg
                ), "eventGeneral");
        if ($ret) {//smaže paragony dané akce
            $cservice = new ChitService();
            $cservice->deleteAll($id);
        }
        return $ret;
    }

    /**
     * znovu otevřít akci
     * @param ID_Event $id
     * @return type 
     */
    public function open($id) {
        return $this->skautIS->event->EventGeneralUpdateOpen(
                        array(
                    "ID" => $id,
                        ), "eventGeneral");
    }

    /**
     * uzavře akci
     * @param int $id - ID akce
     */
    public function close($id) {
        $this->skautIS->event->EventGeneralUpdateClose(
                array(
            "ID" => $id,
                ), "eventGeneral");
    }

    /**
     * kontrolu jestli je možné akci uzavřít
     * @param int $eventId
     * @return bool
     */
    public function isCloseable($eventId) {
        $func = $this->getFunctions($eventId);
        if ($func[0]->ID_Person == NULL) // musí být nastaven vedoucí akce
            return FALSE;
        return TRUE;
    }

    /**
     * kontroluje jestli lze akci upravovat
     * @param ID_Event|stdClass $arg - příjmá bud ID akce nebo jiz akci samotnou
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
        return $arg->ID_EventGeneralState == "draft" ? TRUE : FALSE;
    }

}

