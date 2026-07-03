<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ObjectType;
use App\Model\Cashbook\ReadModel\Queries\CashbookDisplayNameQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\ShouldNotHappen;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\Event\Camp;
use App\Model\Event\Education;
use App\Model\Event\Event;
use App\Model\Event\ReadModel\Queries\CampQuery;
use App\Model\Event\ReadModel\Queries\EducationQuery;
use App\Model\Event\ReadModel\Queries\EventQuery;
use App\Model\Event\SkautisCampId;
use App\Model\Event\SkautisEducationId;
use App\Model\Event\SkautisEventId;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Unit\Unit;

use function assert;
use function sprintf;

final class CashbookDisplayNameQueryHandler
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    public function __invoke(CashbookDisplayNameQuery $query): string
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($query->getCashbookId()));
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

        if ($type->equalsValue(ObjectType::EDUCATION)) {
            $education = $this->queryBus->handle(new EducationQuery(new SkautisEducationId($skautisId)));
            assert($education instanceof Education);

            return $education->getDisplayName();
        }

        throw new ShouldNotHappen(sprintf('Cannot find cashbook name. Unknown object type "%s"', $type->toString()));
    }
}
