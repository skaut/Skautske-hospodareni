<?php

declare(strict_types=1);

namespace Model;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\Skautis\Mapper;
use Nette\Utils\ArrayHash;
use Skautis\Exception;
use Skautis\Skautis;
use Skautis\Wsdl\PermissionException;
use function array_merge;
use function in_array;
use function is_array;

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
        $this->mapper   = $mapper;
        $this->units    = $units;
        $this->queryBus = $queryBus;
    }

    /**
     * vrací všechny akce podle parametrů
     * @param int|null|string $year
     */
    public function getAll($year = null, ?string $state = null) : array
    {
        $events = $this->skautis->event->{'Event' . $this->typeName . 'All'}(['IsRelation' => true, 'ID_Event' . $this->typeName . 'State' => ($state === 'all') ? null : $state, 'Year' => ($year === 'all') ? null : $year]);
        $ret    = [];

        if (is_array($events)) {
            foreach ($events as $e) {
                $cashbookId = $this->mapper->getLocalId($e->ID, $this->type);

                $ret[$e->ID] = (array) $e + $this->getCashbookData($cashbookId);
            }
        }

        return $ret;
    }

    /**
     * vrací detail
     * spojuje data ze skautisu s daty z db
     * @throws PermissionException
     */
    public function get(int $ID) : \stdClass
    {
        $cacheId = __FUNCTION__ . $ID;

        if (! ($res = $this->loadSes($cacheId))) {
            $cashbookId = $this->mapper->getLocalId($ID, $this->type);

            if (in_array($this->type, [ObjectType::EVENT, ObjectType::CAMP], true)) {
                try {
                    $skautisData = (array) $this->skautis->event->{'Event' . $this->typeName . 'Detail'}(['ID' => $ID]);
                } catch (Exception $e) {
                    throw new PermissionException('Nemáte oprávnění pro získání požadovaných informací.', $e instanceof \Exception ? $e->getCode() : 0);
                }
            } elseif ($this->type === ObjectType::UNIT) {
                $skautisData = (array) $this->units->getDetail($ID);
            } else {
                throw new \InvalidArgumentException('Neplatný typ: ' . $this->typeName);
            }

            $data = ArrayHash::from(array_merge($skautisData, $this->getCashbookData($cashbookId)));
            $res  = $this->saveSes($cacheId, $data);
        }

        return $res;
    }

    private function getCashbookData(CashbookId $cashbookId) : array
    {
        /** @var Cashbook $cashbook */
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));
        return [
            'localId' => $cashbookId->toInt(),
            'prefix' => $cashbook->getChitNumberPrefix(),
        ];
    }
}
