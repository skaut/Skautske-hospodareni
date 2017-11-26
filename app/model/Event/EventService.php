<?php

namespace Model;

use Dibi\Connection;
use eGen\MessageBus\Bus\EventBus;
use Model\Event\AssistantNotAdultException;
use Model\Event\Functions;
use Model\Event\LeaderNotAdultException;
use Model\Event\Repositories\IEventRepository;
use Model\Events\Events\EventWasClosed;
use Model\Events\Events\EventWasOpened;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;

/**
 * @author Hána František
 */
class EventService extends MutableBaseService
{

    /** @var EventTable */
    private $table;

    /** @var Cache */
    private $cache;

    /** @var Connection */
    private $connection;

    /** @var IEventRepository */
    private $eventRepository;

    /** @var UnitService */
    private $units;

    /** @var  EventBus */
    private $eventBus;

    public function __construct(
        string $name,
        EventTable $table,
        Skautis $skautis,
        IStorage $cacheStorage,
        Connection $connection,
        IEventRepository $eventRepository,
        EventBus $eventBus,
        UnitService $units
    )
    {
        parent::__construct($name, $skautis);
        /** @var EventTable */
        $this->table = $table;
        $this->cache = new Cache($cacheStorage, __CLASS__);
        $this->connection = $connection;
        $this->eventRepository = $eventRepository;
        $this->eventBus = $eventBus;
        $this->units = $units;
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
     * @throws \Skautis\Wsdl\PermissionException
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
                    $skautisData = (array) $this->units->getDetail($ID);
                } else {
                    throw new \InvalidArgumentException("Neplatný typ: " . $this->typeName);
                }
                $data = \Nette\ArrayHash::from(array_merge($skautisData, $localData));
                $res = $this->saveSes($cacheId, $data);
            }
            return $res;
        } catch (\Skautis\Exception $e) {
            throw new \Skautis\Wsdl\PermissionException("Nemáte oprávnění pro získání požadovaných informací.", $e instanceof \Exception ? $e->getCode() : 0);
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

    public function getSelectedFunctions(int $eventId): Functions
    {
        $data = $this->getFunctions($eventId);

        return new Functions(
            $data[0]->ID_Person,
            $data[1]->ID_Person,
            $data[2]->ID_Person,
            $data[3]->ID_Person
        );
    }

    public function updateFunctions(int $eventId, Functions $functions): void
    {
        $query = [
            "ID" => $eventId,
            "ID_PersonLeader" => $functions->getLeaderId(),
            "ID_PersonAssistant" => $functions->getAssistantId(),
            "ID_PersonEconomist" => $functions->getAccountantId(),
            "ID_PersonMedic" => $functions->getMedicId(),
        ];

        $method = "Event{$this->typeName}UpdateFunction";

        try {

            $this->skautis->event->$method($query);
        } catch (WsdlException $e) {
            if (strpos($e->getMessage(), 'EventFunction_LeaderMustBeAdult') != FALSE) {
                throw new LeaderNotAdultException;
            }
            if (strpos($e->getMessage(), 'EventFunction_AssistantMustBeAdult') !== FALSE) {
                throw new AssistantNotAdultException;
            }
            throw $e;
        }
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
     * @param ChitService $chitService
     * @param string $msg
     */
    public function cancel(int $ID, $chitService, $msg = NULL): bool
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
     * znovu otevřít akci
     */
    public function open(int $id): void
    {
        if($this->type != "general") {
            throw new \RuntimeException("Camp can't be opened!");
        }
        $event = $this->eventRepository->find($id);
        $this->eventRepository->open($event);

        $this->eventBus->handle(new EventWasOpened($event->getId(), $event->getUnitId(), $event->getDisplayName()));
    }

    public function close(int $eventId): void
    {
        if($this->type != "general") {
            throw new \RuntimeException("Camp can't be closed!");
        }
        $event = $this->eventRepository->find($eventId);
        $this->eventRepository->close($event);

        $this->eventBus->handle(new EventWasClosed($event->getId(), $event->getUnitId(), $event->getDisplayName()));
    }

    /**
     * kontrolu jestli je možné uzavřít
     * @param int $ID
     */
    public function isCloseable($ID): bool
    {
        $func = $this->getFunctions($ID);

        return $func[0]->ID_Person != NULL; // musí být nastaven vedoucí akce
    }

    /**
     * @param int $ID
     * @param int $state
     */
    public function activateAutocomputedCashbook($ID, $state = 1): void
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
    public function activateAutocomputedParticipants($ID, $state = 1): void
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
