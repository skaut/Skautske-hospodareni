<?php

declare(strict_types=1);

namespace Model;

use eGen\MessageBus\Bus\QueryBus;
use InvalidArgumentException;
use Model\Cashbook\ObjectType;
use Model\Event\Camp;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\ReadModel\Queries\EventQuery;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Unit;
use Skautis\Skautis;
use function assert;

class EventService extends MutableBaseService
{
    /** @var QueryBus */
    private $queryBus;

    public function __construct(string $name, Skautis $skautis, QueryBus $queryBus)
    {
        parent::__construct($name, $skautis);
        $this->queryBus = $queryBus;
    }

    public function getDisplayName(int $id) : string
    {
        if ($this->type === ObjectType::EVENT) {
            $event = $this->queryBus->handle(new EventQuery(new SkautisEventId($id)));
            assert($event instanceof Event);

            return $event->getUnitName();
        }

        if ($this->type === ObjectType::CAMP) {
            $camp = $this->queryBus->handle(new CampQuery(new SkautisCampId($id)));
            assert($camp instanceof Camp);

            return $camp->getDisplayName();
        }

        if ($this->type === ObjectType::UNIT) {
            $unit = $this->queryBus->handle(new UnitQuery($id));
            assert($unit instanceof Unit);

            return $unit->getDisplayName();
        }

        throw new InvalidArgumentException('Neplatný typ: ' . $this->typeName);
    }
}
