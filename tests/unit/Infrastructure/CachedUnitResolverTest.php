<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services\Unit;

use Codeception\Test\Unit;
use Mockery;
use Model\Payment\IUnitResolver;
use Model\Unit\UnitHasNoParent;
use Nette\Caching\Storages\MemoryStorage;

final class CachedUnitResolverTest extends Unit
{
    public function testResultIsCached() : void
    {
        $unitResolver = Mockery::mock(IUnitResolver::class);
        $unitResolver
            ->shouldReceive('getOfficialUnitId')
            ->once()
            ->withArgs([1])
            ->andReturn(2);

        $cachedResolver = new CachedUnitResolver($unitResolver, new MemoryStorage());

        self::assertSame(2, $cachedResolver->getOfficialUnitId(1));
        self::assertSame(2, $cachedResolver->getOfficialUnitId(1));
    }

    public function testUnitWithNoParentIsNotCached() : void
    {
        $unitResolver = Mockery::mock(IUnitResolver::class);

        $unitResolver
            ->shouldReceive('getOfficialUnitId')
            ->once()
            ->withArgs([1])
            ->andThrow(new UnitHasNoParent());

        $cachedResolver = new CachedUnitResolver($unitResolver, new MemoryStorage());

        try {
            $cachedResolver->getOfficialUnitId(1);
            self::fail();
        } catch (UnitHasNoParent $e) {
            // It's expected
        }

        $unitResolver->shouldReceive('getOfficialUnitId')
            ->once()
            ->withArgs([1])
            ->andReturn(2);

        self::assertSame(2, $cachedResolver->getOfficialUnitId(1));
    }
}
