<?php

namespace Model;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\Queries\CashbookNumberPrefixQuery;
use Model\Skautis\Mapper;
use Nette\Utils\ArrayHash;
use Skautis\Skautis;

class EventService extends MutableBaseService
{

    /** @var UnitService */
    private $units;

    /** @var Mapper */
    private $mapper;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(string $name, Skautis $skautis, Mapper $mapper, UnitService $units, QueryBus $queryBus)
    {
        parent::__construct($name, $skautis);
        $this->mapper = $mapper;
        $this->units = $units;
        $this->queryBus = $queryBus;
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
                $cashbookId = $this->mapper->getLocalId($e->ID, $this->type);

                $ret[$e->ID] = (array)$e + $this->getCashbookData($cashbookId);
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
        $cacheId = __FUNCTION__ . $ID;

        if (!($res = $this->loadSes($cacheId))) {
            $cashbookId = $this->mapper->getLocalId($ID, $this->type);

            if (in_array($this->type, [self::TYPE_GENERAL, self::TYPE_CAMP])) {
                try {
                    $skautisData = (array)$this->skautis->event->{"Event" . $this->typeName . "Detail"}(["ID" => $ID]);
                } catch (\Skautis\Exception $e) {
                    throw new \Skautis\Wsdl\PermissionException("Nemáte oprávnění pro získání požadovaných informací.", $e instanceof \Exception ? $e->getCode() : 0);
                }
            } elseif ($this->type == self::TYPE_UNIT) {
                $skautisData = (array)$this->units->getDetail($ID);
            } else {
                throw new \InvalidArgumentException("Neplatný typ: " . $this->typeName);
            }

            $data = ArrayHash::from(array_merge($skautisData, $this->getCashbookData($cashbookId)));
            $res = $this->saveSes($cacheId, $data);
        }

        return $res;
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

    private function getCashbookData(CashbookId $cashbookId): array
    {
        return [
            'localId' => $cashbookId->toInt(),
            'prefix' => $this->queryBus->handle(new CashbookNumberPrefixQuery($cashbookId)),
        ];
    }

}
