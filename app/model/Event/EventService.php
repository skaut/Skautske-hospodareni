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
use Model\Event\Camp;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\ReadModel\Queries\EventQuery;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Unit;
use Skautis\Exception;
use Skautis\Skautis;
use Skautis\Wsdl\PermissionException;
use function assert;
use function in_array;

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

    public function getDisplayName(int $id)
    {
        if($this->type === ObjectType::EVENT) {
            $event = $this->queryBus->handle (new EventQuery(new SkautisEventId($id)));
            assert ($event instanceof Event);

            return $event->getUnitName ();
        } elseif($this->type === ObjectType::CAMP) {
            $camp = $this->queryBus->handle (new CampQuery(new SkautisCampId($id)));
            assert ($camp instanceof Camp);

            return $camp->getUnitName ();
        } elseif ($this->type === ObjectType::UNIT) {
            $unit = $this->queryBus->handle(new UnitQuery($id));
            assert($unit instanceof Unit);

            return $unit->getDisplayName();
        }

        throw new InvalidArgumentException('Neplatný typ: ' . $this->typeName);
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
