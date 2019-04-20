<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\EventListQuery;
use Model\Event\SkautisEventId;
use Model\Payment\Group;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\ReadModel\Queries\EventsWithoutGroupQuery;
use Model\Payment\Repositories\IGroupRepository;

final class EventsWithoutGroupQueryHandlerTest extends Unit
{
    private const YEAR = 2018;

    public function test() : void
    {
        $queryBus = \Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->withArgs(function (EventListQuery $query) : bool {
                return $query->getYear() === self::YEAR;
            })
            ->andReturn([
                \Mockery::mock(Event::class, ['getId' => new SkautisEventId(4)]),
                \Mockery::mock(Event::class, ['getId' => new SkautisEventId(2)]),
            ]);

        $groups = \Mockery::mock(IGroupRepository::class);
        $groups->shouldReceive('findBySkautisEntities')
            ->once()
            ->withArgs(function (SkautisEntity $first, SkautisEntity $second) : bool {
                return $first->getType()->equalsValue(Type::EVENT)
                    && $second->getType()->equalsValue(Type::EVENT)
                    && $first->getId() === 4
                    && $second->getId() === 2;
            })->andReturn([
                \Mockery::mock(Group::class, ['getObject' => new SkautisEntity(2, Type::get(Type::EVENT))]),
            ]);

        $handler = new EventsWithoutGroupQueryHandler($queryBus, $groups);

        $events = $handler(new EventsWithoutGroupQuery(self::YEAR));

        self::assertCount(1, $events);
        self::assertSame(4, $events[4]->getId()->toInt());
    }
}
