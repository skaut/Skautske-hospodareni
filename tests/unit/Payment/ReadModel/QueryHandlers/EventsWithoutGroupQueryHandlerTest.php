<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Common\Services\QueryBus;
use App\Model\Event\Event;
use App\Model\Event\ReadModel\Queries\EventListQuery;
use App\Model\Event\SkautisEventId;
use App\Model\Payment\Group;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\Group\Type;
use App\Model\Payment\ReadModel\Queries\EventsWithoutGroupQuery;
use App\Model\Payment\Repositories\IGroupRepository;
use Codeception\Test\Unit;
use Mockery;

final class EventsWithoutGroupQueryHandlerTest extends Unit
{
    private const YEAR = 2018;

    public function test(): void
    {
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->withArgs(static function (EventListQuery $query): bool {
                return $query->getYear() === self::YEAR;
            })
            ->andReturn([
                Mockery::mock(Event::class, ['getId' => new SkautisEventId(4)]),
                Mockery::mock(Event::class, ['getId' => new SkautisEventId(2)]),
            ]);

        $groups = Mockery::mock(IGroupRepository::class);
        $groups->shouldReceive('findBySkautisEntities')
            ->once()
            ->withArgs(static function (SkautisEntity $first, SkautisEntity $second): bool {
                return $first->getType()->equalsValue(Type::EVENT)
                    && $second->getType()->equalsValue(Type::EVENT)
                    && $first->getId() === 4
                    && $second->getId() === 2;
            })->andReturn([
                Mockery::mock(Group::class, ['getObject' => new SkautisEntity(2, Type::get(Type::EVENT))]),
            ]);

        $handler = new EventsWithoutGroupQueryHandler($queryBus, $groups);

        $events = $handler(new EventsWithoutGroupQuery(self::YEAR));

        self::assertCount(1, $events);
        self::assertSame(4, $events[4]->getId()->toInt());
    }
}
