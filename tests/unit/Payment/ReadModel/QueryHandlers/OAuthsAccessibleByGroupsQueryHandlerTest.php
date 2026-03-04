<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Codeception\Test\Unit;
use Entity\GoogleOAuth;
use Mockery as m;
use Model\Common\UnitId;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\IUnitResolver;
use Model\Payment\ReadModel\Queries\OAuthsAccessibleByGroupsQuery;
use Model\Payment\Services\IOAuthAccessChecker;

final class OAuthsAccessibleByGroupsQueryHandlerTest extends Unit
{
    public function testReturnsOnlyOauthsAccessibleToAllUnits(): void
    {
        $allowedOAuth = GoogleOAuth::create(new UnitId(20), 'token-1', 'allowed@example.com');
        $deniedOAuth = GoogleOAuth::create(new UnitId(10), 'token-2', 'denied@example.com');

        $repository = m::mock(IGoogleRepository::class);
        $repository->shouldReceive('findByUnits')
            ->once()
            ->with([10, 20])
            ->andReturn([
                20 => [$allowedOAuth],
                10 => [$deniedOAuth],
            ]);

        $accessChecker = m::mock(IOAuthAccessChecker::class);
        $accessChecker->shouldReceive('allUnitsHaveAccessToOAuth')
            ->once()
            ->with([10], $allowedOAuth->getId())
            ->andReturn(true);
        $accessChecker->shouldReceive('allUnitsHaveAccessToOAuth')
            ->once()
            ->with([10], $deniedOAuth->getId())
            ->andReturn(false);

        $unitResolver = new class() implements IUnitResolver
        {
            public function getOfficialUnitId(int $unitId): int
            {
                return $unitId === 10 ? 20 : $unitId;
            }
        };

        $result = (new OAuthsAccessibleByGroupsQueryHandler($accessChecker, $unitResolver, $repository))(
            new OAuthsAccessibleByGroupsQuery([10]),
        );

        self::assertCount(1, $result);
        self::assertSame('allowed@example.com', $result[0]->getEmail());
        self::assertSame(20, $result[0]->getUnitId());
    }
}
