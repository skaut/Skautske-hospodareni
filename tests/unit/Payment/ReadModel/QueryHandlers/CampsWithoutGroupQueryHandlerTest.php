<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Codeception\Test\Unit;
use eGen\MessageBus\Bus\QueryBus;
use Mockery;
use Model\Event\Camp;
use Model\Event\ReadModel\Queries\CampListQuery;
use Model\Event\SkautisCampId;
use Model\Payment\Group;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\ReadModel\Queries\CampsWithoutGroupQuery;
use Model\Payment\Repositories\IGroupRepository;

final class CampsWithoutGroupQueryHandlerTest extends Unit
{
    private const YEAR = 2018;

    public function test() : void
    {
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->withArgs(static function (CampListQuery $query) : bool {
                return $query->getYear() === self::YEAR;
            })
            ->andReturn([
                Mockery::mock(Camp::class, ['getId' => new SkautisCampId(4)]),
                Mockery::mock(Camp::class, ['getId' => new SkautisCampId(2)]),
            ]);

        $groups = Mockery::mock(IGroupRepository::class);
        $groups->shouldReceive('findBySkautisEntities')
            ->once()
            ->withArgs(static function (SkautisEntity $first, SkautisEntity $second) : bool {
                return $first->getType()->equalsValue(Type::CAMP)
                    && $second->getType()->equalsValue(Type::CAMP)
                    && $first->getId() === 4
                    && $second->getId() === 2;
            })->andReturn([
                Mockery::mock(Group::class, ['getObject' => new SkautisEntity(2, Type::get(Type::CAMP))]),
            ]);

        $handler = new CampsWithoutGroupQueryHandler($queryBus, $groups);

        $camps = $handler(new CampsWithoutGroupQuery(self::YEAR));

        self::assertCount(1, $camps);
        self::assertSame(4, $camps[4]->getId()->toInt());
    }
}
