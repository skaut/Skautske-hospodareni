<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Common\Services\QueryBus;
use App\Model\Event\Camp;
use App\Model\Event\ReadModel\Queries\CampListQuery;
use App\Model\Event\SkautisCampId;
use App\Model\Payment\Group;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\Group\Type;
use App\Model\Payment\ReadModel\Queries\CampsWithoutGroupQuery;
use App\Model\Payment\Repositories\IGroupRepository;
use Codeception\Test\Unit;
use Mockery;

final class CampsWithoutGroupQueryHandlerTest extends Unit
{
    private const YEAR = 2018;

    public function test(): void
    {
        $queryBus = Mockery::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->withArgs(static function (CampListQuery $query): bool {
                return $query->getYear() === self::YEAR;
            })
            ->andReturn([
                Mockery::mock(Camp::class, ['getId' => new SkautisCampId(4)]),
                Mockery::mock(Camp::class, ['getId' => new SkautisCampId(2)]),
            ]);

        $groups = Mockery::mock(IGroupRepository::class);
        $groups->shouldReceive('findBySkautisEntities')
            ->once()
            ->withArgs(static function (SkautisEntity $first, SkautisEntity $second): bool {
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
