<?php

declare(strict_types=1);

namespace Model;

use eGen\MessageBus\Bus\QueryBus;
use InvalidArgumentException;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\SkautisEventId;
use Nette\Utils\ArrayHash;
use Skautis\Exception;
use Skautis\Skautis;
use Skautis\Wsdl\PermissionException;
use function array_merge;
use function assert;
use function in_array;
use function is_array;

class EventService extends MutableBaseService
{
    /** @var UnitService */
    private $units;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(string $name, Skautis $skautis, UnitService $units, QueryBus $queryBus)
    {
        parent::__construct($name, $skautis);
        $this->units    = $units;
        $this->queryBus = $queryBus;
    }

    /**
     * vrací všechny akce podle parametrů
     *
     * @param int|string|null $year
     *
     * @return mixed[]
     */
    public function getAll($year = null, ?string $state = null) : array
    {
        $events = $this->skautis->event->{'Event' . $this->typeName . 'All'}(['IsRelation' => true, 'ID_Event' . $this->typeName . 'State' => $state === 'all' ? null : $state, 'Year' => $year === 'all' ? null : $year]);
        $ret    = [];

        if (is_array($events)) {
            foreach ($events as $e) {
                $ret[$e->ID] = (array) $e + $this->getCashbookData($e->ID);
            }
        }

        return $ret;
    }

    /**
     * vrací detail
     * spojuje data ze skautisu s daty z db
     *
     * @throws PermissionException
     */
    public function get(int $ID) : ArrayHash
    {
        $cacheId = __FUNCTION__ . $ID;

        $res = $this->loadSes($cacheId);
        if (! $res) {
            if (in_array($this->type, [ObjectType::EVENT, ObjectType::CAMP], true)) {
                try {
                    $skautisData = (array) $this->skautis->event->{'Event' . $this->typeName . 'Detail'}(['ID' => $ID]);
                } catch (Exception $e) {
                    throw new PermissionException('Nemáte oprávnění pro získání požadovaných informací.', $e instanceof \Exception ? $e->getCode() : 0);
                }
            } elseif ($this->type === ObjectType::UNIT) {
                $skautisData = (array) $this->units->getDetail($ID);
            } else {
                throw new InvalidArgumentException('Neplatný typ: ' . $this->typeName);
            }

            $data = ArrayHash::from(array_merge($skautisData, $this->getCashbookData($ID)));
            $res  = $this->saveSes($cacheId, $data);
        }

        return $res;
    }

    /**
     * @return mixed[]
     */
    private function getCashbookData(int $eventId) : array
    {
        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($eventId)));

        assert($cashbookId instanceof CashbookId);

        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        assert($cashbook instanceof Cashbook);

        return [
            'prefix' => $cashbook->getChitNumberPrefix(),
        ];
    }
}
