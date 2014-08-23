<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class EventService extends MutableBaseService {

    protected static $ID_Functions = array("ID_PersonLeader", "ID_PersonAssistant", "ID_PersonEconomist");

    public function __construct($name, $longName, $expire, $skautIS, $cacheStorage, $connection) {
        parent::__construct($name, $longName, $expire, $skautIS, $cacheStorage, $connection);
        /** @var EventTable */
        $this->table = new EventTable($connection);
    }

    public function getAll($year = NULL, $state = NULL) {
        $year = ($year == "all") ? NULL : $year;
        $state = ($state == "all") ? NULL : $state;
        
        $events = $this->skautIS->event->{"Event" . self::$typeName . "All"}(array("IsRelation" => TRUE, "ID_Event" . self::$typeName . "State" => $state, "Year" => $year));
        if(is_array($events)){
            usort($events, function ($a, $b) {
                $at = strtotime($a->StartDate);
                $bt = strtotime($b->StartDate);
                return ($at == $bt) ? strcasecmp($a->DisplayName, $b->DisplayName) : ($at > $bt  ? 1 : -1);
            });
        }
        return $events;
    }

    public function getLocalId($skautisEventId) {
        $cacheId = __FUNCTION__ . $skautisEventId;
        if (!($res = $this->loadSes($cacheId))) {
            $res = $this->saveSes($cacheId, $this->table->getLocalId($skautisEventId, self::$type));
        }
        return $res;
    }

    public function getSkautisId($localEventId) {
        $cacheId = __FUNCTION__ . $localEventId;
        if (!($res = $this->loadSes($cacheId))) {
            $res = $this->saveSes($cacheId, $this->table->getSkautisId($localEventId, self::$type));
        }
        return $res;
    }

    /**
     * vrací detail
     * spojuje data ze skautisu s daty z db
     * @param ID_Event $ID
     * @return stdClass 
     */
    public function get($ID) {
        try {
            $cacheId = __FUNCTION__ . $ID;
            if (!($res = $this->loadSes($cacheId))) {
                $skautisData = (array) $this->skautIS->event->{"Event" . self::$typeName . "Detail"}(array("ID" => $ID));
                $tableData = (array) $this->table->getByEventId($ID, self::$type);
//                unset($tableData['skautisId']);
                $ev = \Nette\ArrayHash::from(array_merge($skautisData, $tableData));
                $res = $this->saveSes($cacheId, $ev);
            }
            return $res;
        } catch (\SkautIS\Exception\BaseException $e) {
            throw new \SkautIS\Exception\PermissionException("Nemáte oprávnění pro získání požadovaných informací.", $e->getCode());
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
    protected function getPreparedFunctions($ID_Event) {
        $data = $this->getFunctions($ID_Event);
        $query = array("ID" => $ID_Event);
        for ($i = 0; $i < 3; $i++) {
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
        $query[self::$ID_Functions[$ID_Function]] = $ID_Person; //nova změna
        return $this->skautIS->event->{self::$typeLongName . "UpdateFunction"}($query);
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
            $this->cache->save($cacheId, $ret);
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
            $this->cache->save($cacheId, $ret);
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
            $this->cache->save($cacheId, $ret);
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
    public function create($name, $start, $end, $location = " ", $unit = NULL, $scope = NULL, $type = NULL) {
        $scope = $scope !== NULL ? $scope : 2; //3-stedisko, 2-oddil
        $type = $type !== NULL ? $type : 2; //2-vyprava
        $unit = $unit !== NULL ? $unit : $this->skautIS->getUnitId();

        $location = !empty($location) && $location != NULL ? $location : " ";

        $ret = $this->skautIS->event->EventGeneralInsert(
                array(
            "ID" => 1, //musi byt neco nastavene
            "Location" => $location,
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

        if (isset($ret->ID)) {
            return $ret->ID;
        }
        return $ret;
    }

    /**
     * @param type $data
     */
    public function updatePrefix($skautisId, $prefix) {
        return $this->table->updatePrefix($skautisId, strtolower(self::$typeName), $prefix);
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

        if (isset($data['prefix'])) {
            $this->updatePrefix($ID, $data['prefix']);
            unset($data['prefix']);
        }

        $ret = $this->skautIS->event->EventGeneralUpdate(array(
            "ID" => $ID,
            "Location" => $data['location'],
            "Note" => $old->Note,
            "ID_EventGeneralScope" => isset($data['scope']) ? $data['scope'] : $old->ID_EventGeneralScope,
            "ID_EventGeneralType" => isset($data['type']) ? $data['type'] : $old->ID_EventGeneralType,
            "ID_Unit" => $old->ID_Unit,
            "DisplayName" => $data['name'],
            "StartDate" => $data['start'],
            "EndDate" => $data['end'],
                ), "eventGeneral");



//            $this->skautIS->event->EventGeneralUpdateFunction(array(
//                "ID" => $ID,
//                "ID_PersonLeader" => $data['leader'],
//                "ID_PersonAssistant" => $data['assistant'],
//                "ID_PersonEconomist" => $data['economist'],
//            ));

        if (isset($ret->ID)) {
            return $ret->ID;
        }
        return $ret;
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
            \Nette\Environment::getContext()->eventService->chits->deleteAll($ID);
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
        if ($func[0]->ID_Person != NULL) { // musí být nastaven vedoucí akce
            return TRUE;
        }
        return FALSE;
    }

    /**
     * kontroluje jestli lze upravovat
     * @param ID_|stdClass $arg - příjmá bud ID nebo jiz akci samotnou
     * @return bool
     */
    public function isCommandEditable($arg) {
        if (!$arg instanceof \stdClass) {
            try {
                $arg = $this->get($arg);
            } catch (\SkautIS\Exception\PermissionException $exc) {
                return FALSE;
            }
        }
        return $arg->{"ID_Event" . self::$typeName . "State"} == "draft" ? TRUE : FALSE;
    }

    /**
     * 
     * @param type $ID
     * @param type $state
     */
    public function activateAutocomputedCashbook($ID, $state = 1) {
        $this->skautIS->event->{"EventCampUpdateRealTotalCostBeforeEnd"}(
                array(
            "ID" => $ID,
            "IsRealTotalCostAutoComputed" => $state
                ), "event" . self::$typeName
        );
    }

    /**
     * aktivuje automatické dopočítávání pro seznam osobodnů z tabulky účastníků
     * @param type $ID
     * @param type $state
     */
    public function activateAutocomputedParticipants($ID, $state = 1) {
        $this->skautIS->event->{"EventCampUpdateAdult"}(array("ID" => $ID, "IsRealAutoComputed" => $state), "event" . self::$typeName);
    }

    /**
     * vrací počet událostí s vyplněným záznamem v pokladní kníze, který nebyl smazán
     * @return int počet událostí se záznamem
     */
    public function getCountOfActiveEvents() {
        return $this->connection->query("SELECT COUNT(DISTINCT actionId) FROM [" . BaseTable::TABLE_CHIT . "] WHERE deleted = 0")->fetchSingle();
    }

}
