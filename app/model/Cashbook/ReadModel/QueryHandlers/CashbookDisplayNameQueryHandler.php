<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookDisplayNameQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Model\Common\ShouldNotHappen;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\Camp;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\ReadModel\Queries\EventQuery;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Unit;
use function assert;
use function sprintf;

final class CashbookDisplayNameQueryHandler
{
    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function __invoke(CashbookDisplayNameQuery $query) : string
    {
        $cashbook  = $this->queryBus->handle(new CashbookQuery($query->getCashbookId()));
        $skautisId = $this->queryBus->handle(new SkautisIdQuery($query->getCashbookId()));
        assert($cashbook instanceof Cashbook);

        $type = $cashbook->getType()->getSkautisObjectType();

        if ($type->equalsValue(ObjectType::EVENT)) {
            $event = $this->queryBus->handle(new EventQuery(new SkautisEventId($skautisId)));
            assert($event instanceof Event);

            return $event->getUnitName();
        }

        if ($type->equalsValue(ObjectType::CAMP)) {
            $camp = $this->queryBus->handle(new CampQuery(new SkautisCampId($skautisId)));
            assert($camp instanceof Camp);

            return $camp->getDisplayName();
        }

        if ($type->equalsValue(ObjectType::UNIT)) {
            $unit = $this->queryBus->handle(new UnitQuery($skautisId));
            assert($unit instanceof Unit);

            return $unit->getDisplayName();
        }

        throw new ShouldNotHappen(sprintf('Cannot find cashbook name. Unknown object type "%s"', $type->toString()));
    }
}
