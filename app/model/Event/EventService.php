<?php

namespace Model;
use Dibi\Connection;
use Model\Event\AssistantNotAdultException;
use Model\Event\LeaderNotAdultException;
use Nette\Caching\IStorage;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;

/**
 * @author Hána František
 */
class EventService extends MutableBaseService
{
    
    protected static $ID_Functions = array("ID_PersonLeader", "ID_PersonAssistant", "ID_PersonEconomist", "ID_PersonMedic");

    public function __construct(string $name, Skautis $skautis, IStorage $cacheStorage, Connection $connection)
    {
        parent::__construct($name, $skautis, $cacheStorage, $connection);
        /** @var EventTable */
        $this->table = new EventTable($connection);
    }

    /**
     * vrací všechny akce podle parametrů
     * @param type $year
     * @param type $state
     * @return type
     */
    public function getAll($year = NULL, $state = NULL) {
        $events = $this->skautis->event->{"Event" . $this->typeName . "All"}(array("IsRelation" => TRUE, "ID_Event" . $this->typeName . "State" => ($state == "all") ? NULL : $state, "Year" => ($year == "all") ? NULL : $year));
        $ret = array();
        if (is_array($events)) {
            foreach ($events as $e) {
                $ret[$e->ID] = (array) $e + (array) $this->table->getByEventId($e->ID, $this->type);
            }
        }
        return $ret;
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
                $localData = (array) $this->table->getByEventId($ID, $this->type);
                if (in_array($this->type, array(self::TYPE_GENERAL, self::TYPE_CAMP))) {
                    $skautisData = (array) $this->skautis->event->{"Event" . $this->typeName . "Detail"}(array("ID" => $ID));
                } elseif ($this->type == self::TYPE_UNIT) {
                    $skautisData = (array) $this->skautis->org->{"UnitDetail"}(array("ID" => $ID));
                } else {
                    throw new \InvalidArgumentException("Neplatný typ: " . $this->typeName);
                }
                $data = \Nette\ArrayHash::from(array_merge($skautisData, $localData));
                $res = $this->saveSes($cacheId, $data);
            }
            return $res;
        } catch (Skautis\Exception $e) {
            throw new \Skautis\Wsdl\PermissionException("Nemáte oprávnění pro získání požadovaných informací.", $e->getCode());
        }
    }

    /**
     * vrací obsazení funkcí na zadané akci
     * @param int $unitId
     * @return \stdClass[]
     */
    public function getFunctions($unitId) {
        return $this->skautis->event->{"EventFunctionAll" . $this->typeName}(array("ID_Event" . $this->typeName => $unitId));
    }

    /**
     * vrátí data funkcí připravená pro update
     * @param type $ID_Event
     * @return type
     */
    protected function getPreparedFunctions($ID_Event) {
        $data = $this->getFunctions($ID_Event);
        $query = array("ID" => $ID_Event);
        for ($i = 0; $i <= 3; $i++) {
            $query[self::$ID_Functions[$i]] = $data[$i]->ID_Person;
        }
        return $query;
    }

    /**
     * nastaví danou funkci
     * @param int $ID_Event
     * @param int $ID_Person
     * @param int $ID_Function
     * @return type
     */
    public function setFunction($ID_Event, $ID_Person, $ID_Function) {
        $query = $this->getPreparedFunctions($ID_Event);
        $query[self::$ID_Functions[$ID_Function]] = $ID_Person; //nova změna

        try {
            return $this->skautis->event->{"Event" . $this->typeName . "UpdateFunction"}($query);
        } catch(WsdlException $e) {
            if(strpos($e->getMessage(), 'EventFunction_LeaderMustBeAdult') != FALSE) {
                throw new LeaderNotAdultException;
            }
            if(strpos($e->getMessage(), 'EventFunction_AssistantMustBeAdult') !== FALSE) {
                throw new AssistantNotAdultException;
            }
        }

    }

    /**
     * vrací seznam všech stavů akce
     * používá Cache
     * @return array
     */
    public function getStates() {
        $cacheId = __FUNCTION__ . $this->typeName;
        if (!($ret = $this->cache->load($cacheId))) {
            $res = $this->skautis->event->{"Event" . $this->typeName . "StateAll"}();
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
        $cacheId = __FUNCTION__ . $this->typeName;
        if (!($ret = $this->cache->load($cacheId))) {
            $res = $this->skautis->event->EventGeneralScopeAll();
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
        $cacheId = __FUNCTION__ . $this->typeName;
        if (!($ret = $this->cache->load($cacheId))) {
            $res = $this->skautis->event->{($this->typeName != "Camp" ? "Event" : "") . $this->typeName . "TypeAll"}();
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
    public function create($name, $start, $end, $location = NULL, $unit = NULL, $scope = NULL, $type = NULL) {
        $scope = $scope !== NULL ? $scope : 2; //3-stedisko, 2-oddil
        $type = $type !== NULL ? $type : 2; //2-vyprava
        $unit = $unit !== NULL ? $unit : $this->skautis->getUser()->getUnitId();

        $location = !empty($location) && $location !== NULL ? $location : " ";

        $ret = $this->skautis->event->EventGeneralInsert(
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
//        $this->skautis->event->EventGeneralUpdateFunction(array(
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
        return $this->table->updatePrefix($skautisId, strtolower($this->typeName), $prefix);
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

        $ret = $this->skautis->event->EventGeneralUpdate(array(
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



//            $this->skautis->event->EventGeneralUpdateFunction(array(
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
     * @param ChitService $chitService
     * @param string $msg
     * @return type 
     */
    public function cancel($ID, $chitService, $msg = NULL) {
        $ret = $this->skautis->event->{"Event" . $this->typeName . "UpdateCancel"}(array(
            "ID" => $ID,
            "CancelDecision" => !is_null($msg) ? $msg : " "
                ), "event" . $this->typeName);
        if ($ret) {//smaže paragony
            $chitService->deleteAll($ID);
        }
        return $ret;
    }

    /**
     * znovu otevřít
     * @param ID_ $ID
     * @return type 
     */
    public function open($ID) {
        return $this->skautis->event->{"Event" . $this->typeName . "UpdateOpen"}(
                        array(
                    "ID" => $ID,
                        ), "event" . $this->typeName);
    }

    /**
     * uzavře 
     * @param int $ID - ID akce
     */
    public function close($ID) {
        $this->skautis->event->{"Event" . $this->typeName . "UpdateClose"}(
                array(
            "ID" => $ID,
                ), "event" . $this->typeName);
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
            } catch (\Skautis\Wsdl\PermissionException $exc) {
                return FALSE;
            }
        }
        return $arg->{"ID_Event" . $this->typeName . "State"} == "draft" ? TRUE : FALSE;
    }

    /**
     * 
     * @param type $ID
     * @param type $state
     */
    public function activateAutocomputedCashbook($ID, $state = 1) {
        $this->skautis->event->{"EventCampUpdateRealTotalCostBeforeEnd"}(
                array(
            "ID" => $ID,
            "IsRealTotalCostAutoComputed" => $state
                ), "event" . $this->typeName
        );
    }

    /**
     * aktivuje automatické dopočítávání pro seznam osobodnů z tabulky účastníků
     * @param type $ID
     * @param type $state
     */
    public function activateAutocomputedParticipants($ID, $state = 1) {
        $this->skautis->event->{"EventCampUpdateAdult"}(array("ID" => $ID, "IsRealAutoComputed" => $state), "event" . $this->typeName);
    }

    /**
     * vrací počet událostí s vyplněným záznamem v pokladní kníze, který nebyl smazán
     * @return int počet událostí se záznamem
     */
    public function getCountOfActiveEvents() {
        return $this->connection->query("SELECT COUNT(DISTINCT actionId) FROM [" . BaseTable::TABLE_CHIT . "] WHERE deleted = 0")->fetchSingle();
    }

}
