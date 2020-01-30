<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services\Unit;

use Codeception\Test\Unit as TestCase;
use Mockery;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Model\Unit\UnitNotFound;
use Nette\Caching\Storages\MemoryStorage;

final class CachedUnitRepositoryTest extends TestCase
{
    public function testFindResultIsCached() : void
    {
        $id   = 1;
        $unit = $this->unit($id);

        $repository = Mockery::mock(IUnitRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->withArgs([$id])
            ->andReturn($unit);

        $cachedRepository = new CachedUnitRepository($repository, new MemoryStorage());

        $this->assertSame($unit, $cachedRepository->find($id));
        $this->assertSame($unit, $cachedRepository->find($id));
    }

    public function testFindExceptionIsRethrown() : void
    {
        $exception = new UnitNotFound();

        $repository = Mockery::mock(IUnitRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->withArgs([1])
            ->andThrow($exception);

        $this->expectExceptionObject($exception);

        (new CachedUnitRepository($repository, new MemoryStorage()))->find(1);
    }

    public function testFindByParentResultIsCached() : void
    {
        $units = [$this->unit(2)];

        $repository = Mockery::mock(IUnitRepository::class);
        $repository->shouldReceive('findByParent')
            ->once()
            ->withArgs([1])
            ->andReturn($units);

        $cachedRepository = new CachedUnitRepository($repository, new MemoryStorage());

        $this->assertSame($units, $cachedRepository->findByParent(1));
        $this->assertSame($units, $cachedRepository->findByParent(1));
    }

    private function unit(int $id) : Unit
    {
        return new Unit($id, '', '', null, '', '', '', '', '', null, []);
    }
}
