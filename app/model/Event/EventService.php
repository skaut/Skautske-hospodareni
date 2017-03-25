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

    protected static $ID_Functions = ["ID_PersonLeader", "ID_PersonAssistant", "ID_PersonEconomist", "ID_PersonMedic"];

    /** @var EventTable */
    private $table;

    /** @var Connection */
    private $connection;

    public function __construct(string $name, EventTable $table, Skautis $skautis, IStorage $cacheStorage, Connection $connection)
    {
        parent::__construct($name, $skautis, $cacheStorage);
        /** @var EventTable */
        $this->table = $table;
        $this->connection = $connection;
    }

    /**
     * vrací všechny akce podle parametrů
     * @param int|NULL $year
     * @param string|NULL $state
     * @return array
     */
    public function getAll($year = NULL, $state = NULL)
    {
        $events = $this->skautis->event->{"Event" . $this->typeName . "All"}(["IsRelation" => TRUE, "ID_Event" . $this->typeName . "State" => ($state == "all") ? NULL : $state, "Year" => ($year == "all") ? NULL : $year]);
        $ret = [];
        if (is_array($events)) {
            foreach ($events as $e) {
                $ret[$e->ID] = (array)$e + (array)$this->table->getByEventId($e->ID, $this->type);
            }
        }
        return $ret;
    }

    /**
     * vrací detail
     * spojuje data ze skautisu s daty z db
     * @param int $ID
     * @return \stdClass
     */
    public function get($ID)
    {
        try {
            $cacheId = __FUNCTION__ . $ID;
            if (!($res = $this->loadSes($cacheId))) {
                $localData = (array)$this->table->getByEventId($ID, $this->type);
                if (in_array($this->type, [self::TYPE_GENERAL, self::TYPE_CAMP])) {
                    $skautisData = (array)$this->skautis->event->{"Event" . $this->typeName . "Detail"}(["ID" => $ID]);
                } elseif ($this->type == self::TYPE_UNIT) {
                    $skautisData = (array)$this->skautis->org->{"UnitDetail"}(["ID" => $ID]);
                } else {
                    throw new \InvalidArgumentException("Neplatný typ: " . $this->typeName);
                }
                $data = \Nette\ArrayHash::from(array_merge($skautisData, $localData));
                $res = $this->saveSes($cacheId, $data);
            }
            return $res;
        } catch (\Skautis\Exception $e) {
            throw new \Skautis\Wsdl\PermissionException("Nemáte oprávnění pro získání požadovaných informací.", $e->getCode());
        }
    }

    /**
     * vrací obsazení funkcí na zadané akci
     * @param int $unitId
     * @return \stdClass[]
     */
    public function getFunctions($unitId)
    {
        return $this->skautis->event->{"EventFunctionAll" . $this->typeName}(["ID_Event" . $this->typeName => $unitId]);
    }

    /**
     * vrátí data funkcí připravená pro update
     * @param int $ID_Event
     * @return string[]
     */
    protected function getPreparedFunctions($ID_Event)
    {
        $data = $this->getFunctions($ID_Event);
        $query = ["ID" => $ID_Event];
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
     * @return bool
     * @throws LeaderNotAdultException
     * @throws AssistantNotAdultException
     */
    public function setFunction($ID_Event, $ID_Person, $ID_Function)
    {
        $query = $this->getPreparedFunctions($ID_Event);
        $query[self::$ID_Functions[$ID_Function]] = $ID_Person; //nova změna

        try {
            return (bool)$this->skautis->event->{"Event" . $this->typeName . "UpdateFunction"}($query);
        } catch (WsdlException $e) {
            if (strpos($e->getMessage(), 'EventFunction_LeaderMustBeAdult') != FALSE) {
                throw new LeaderNotAdultException;
            }
            if (strpos($e->getMessage(), 'EventFunction_AssistantMustBeAdult') !== FALSE) {
                throw new AssistantNotAdultException;
            }
        }
        return FALSE;
    }

    /**
     * vrací seznam všech stavů akce
     * používá Cache
     * @return string[]
     */
    public function getStates()
    {
        $cacheId = __FUNCTION__ . $this->typeName;
        if (!($ret = $this->cache->load($cacheId))) {
            $res = $this->skautis->event->{"Event" . $this->typeName . "StateAll"}();
            $ret = [];
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
    public function getScopes()
    {
        $cacheId = __FUNCTION__ . $this->typeName;
        if (!($ret = $this->cache->load($cacheId))) {
            $res = $this->skautis->event->EventGeneralScopeAll();
            $ret = [];
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
    public function getTypes()
    {
        $cacheId = __FUNCTION__ . $this->typeName;
        if (!($ret = $this->cache->load($cacheId))) {
            $res = $this->skautis->event->{($this->typeName != "Camp" ? "Event" : "") . $this->typeName . "TypeAll"}();
            $ret = [];
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
     * @param string $start datum zacatku
     * @param string $end datum konce
     * @param int $unit ID jednotky
     * @param int $scope rozsah zaměření akce
     * @param int $type typ akce
     * @return int|\stdClass ID akce
     */
    public function create($name, $start, $end, $location = NULL, $unit = NULL, $scope = NULL, $type = NULL)
    {
        $scope = $scope !== NULL ? $scope : 2; //3-stedisko, 2-oddil
        $type = $type !== NULL ? $type : 2; //2-vyprava
        $unit = $unit !== NULL ? $unit : $this->skautis->getUser()->getUnitId();

        $location = !empty($location) && $location !== NULL ? $location : " ";

        $ret = $this->skautis->event->EventGeneralInsert(
            [
                "ID" => 1, //musi byt neco nastavene
                "Location" => $location,
                "Note" => " ", //musi byt neco nastavene
                "ID_EventGeneralScope" => $scope,
                "ID_EventGeneralType" => $type,
                "ID_Unit" => $unit,
                "DisplayName" => $name,
                "StartDate" => $start,
                "EndDate" => $end,
                "IsStatisticAutoComputed" => FALSE,
            ], "eventGeneral");

        if (isset($ret->ID)) {
            return $ret->ID;
        }
        return $ret;
    }

    /**
     * @param int $skautisId
     * @param string $prefix
     * @return bool
     */
    public function updatePrefix($skautisId, $prefix): bool
    {
        return (bool)$this->table->updatePrefix($skautisId, strtolower($this->typeName), $prefix);
    }

    /**
     * aktualizuje informace o akci
     * EventGeneral specific
     * @param array $data
     * @return int
     */
    public function update(array $data)
    {
        $ID = $data['aid'];
        $old = $this->get($ID);

        if (isset($data['prefix'])) {
            $this->updatePrefix($ID, $data['prefix']);
            unset($data['prefix']);
        }

        $ret = $this->skautis->event->EventGeneralUpdate([
            "ID" => $ID,
            "Location" => $data['location'],
            "Note" => $old->Note,
            "ID_EventGeneralScope" => isset($data['scope']) ? $data['scope'] : $old->ID_EventGeneralScope,
            "ID_EventGeneralType" => isset($data['type']) ? $data['type'] : $old->ID_EventGeneralType,
            "ID_Unit" => $old->ID_Unit,
            "DisplayName" => $data['name'],
            "StartDate" => $data['start'],
            "EndDate" => $data['end'],
        ], "eventGeneral");

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
     * @return bool
     */
    public function cancel($ID, $chitService, $msg = NULL) : bool
    {
        $ret = $this->skautis->event->{"Event" . $this->typeName . "UpdateCancel"}([
            "ID" => $ID,
            "CancelDecision" => !is_null($msg) ? $msg : " "
        ], "event" . $this->typeName);
        if ($ret) {//smaže paragony
            $chitService->deleteAll($ID);
        }
        return (bool)$ret;
    }

    /**
     * znovu otevřít
     * @param int $ID
     */
    public function open($ID)
    {
        $this->skautis->event->{"Event" . $this->typeName . "UpdateOpen"}(
            [
                "ID" => $ID,
            ], "event" . $this->typeName);
    }

    /**
     * uzavře
     * @param int $ID - ID akce
     */
    public function close($ID)
    {
        $this->skautis->event->{"Event" . $this->typeName . "UpdateClose"}(
            [
                "ID" => $ID,
            ], "event" . $this->typeName);
    }

    /**
     * kontrolu jestli je možné uzavřít
     * @param int $ID
     * @return bool
     */
    public function isCloseable($ID)
    {
        $func = $this->getFunctions($ID);
        if ($func[0]->ID_Person != NULL) { // musí být nastaven vedoucí akce
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @param int $ID
     * @param int $state
     */
    public function activateAutocomputedCashbook($ID, $state = 1)
    {
        $this->skautis->event->{"EventCampUpdateRealTotalCostBeforeEnd"}(
            [
                "ID" => $ID,
                "IsRealTotalCostAutoComputed" => $state
            ], "event" . $this->typeName
        );
    }

    /**
     * aktivuje automatické dopočítávání pro seznam osobodnů z tabulky účastníků
     * @param int $ID
     * @param int $state
     */
    public function activateAutocomputedParticipants($ID, $state = 1)
    {
        $this->skautis->event->{"EventCampUpdateAdult"}(["ID" => $ID, "IsRealAutoComputed" => $state], "event" . $this->typeName);
    }

    /**
     * vrací počet událostí s vyplněným záznamem v pokladní kníze, který nebyl smazán
     * @return int počet událostí se záznamem
     */
    public function getCountOfActiveEvents()
    {
        return $this->connection->query("SELECT COUNT(DISTINCT actionId) FROM [" . BaseTable::TABLE_CHIT . "] WHERE deleted = 0")->fetchSingle();
    }

}
