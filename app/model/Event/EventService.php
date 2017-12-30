<?php

namespace Model;

use Model\Skautis\Mapper;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Skautis\Skautis;

class EventService extends MutableBaseService
{

    /** @var EventTable */
    private $table;

    /** @var Cache */
    private $cache;

    /** @var UnitService */
    private $units;

    /** @var Mapper */
    private $mapper;

    public function __construct(
        string $name,
        EventTable $table,
        Skautis $skautis,
        IStorage $cacheStorage,
        Mapper $mapper,
        UnitService $units
    )
    {
        parent::__construct($name, $skautis);
        $this->table = $table;
        $this->cache = new Cache($cacheStorage, __CLASS__);
        $this->mapper = $mapper;
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
                $this->mapper->getLocalId($e->ID, $this->type); // called only to create record in ac_object
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
                $this->mapper->getLocalId($ID, $this->type); // called only to create record in ac_object
                $localData = (array)$this->table->getByEventId($ID, $this->type);
                if (in_array($this->type, [self::TYPE_GENERAL, self::TYPE_CAMP])) {
                    $skautisData = (array)$this->skautis->event->{"Event" . $this->typeName . "Detail"}(["ID" => $ID]);
                } elseif ($this->type == self::TYPE_UNIT) {
                    $skautisData = (array) $this->units->getDetail($ID);
                } else {
                    throw new \InvalidArgumentException("Neplatný typ: " . $this->typeName);
                }
                $data = \Nette\Utils\ArrayHash::from(array_merge($skautisData, $localData));
                $res = $this->saveSes($cacheId, $data);
            }
            return $res;
        } catch (\Skautis\Exception $e) {
            throw new \Skautis\Wsdl\PermissionException("Nemáte oprávnění pro získání požadovaných informací.", $e instanceof \Exception ? $e->getCode() : 0);
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
     * @param int $skautisId
     * @param string $prefix
     */
    public function updatePrefix($skautisId, $prefix): bool
    {
        $localId = $this->mapper->getLocalId($skautisId, strtolower($this->typeName));
        return $this->table->updatePrefix($localId , $prefix);
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
            $this->updatePrefix((int)$ID, $data['prefix']);
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
     * aktivuje automatické dopočítávání pro seznam osobodnů z tabulky účastníků
     * @param int $ID
     * @param int $state
     */
    public function activateAutocomputedParticipants($ID, $state = 1): void
    {
        $this->skautis->event->{"EventCampUpdateAdult"}(["ID" => $ID, "IsRealAutoComputed" => $state], "event" . $this->typeName);
    }

}
