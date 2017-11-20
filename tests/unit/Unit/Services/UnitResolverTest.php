<?php

namespace Model\Unit\Services;

use Mockery as m;
use Model\Unit\Repositories\IUnitRepository;

class UnitResolverTest extends \Codeception\Test\Unit
{

    public function testResolveOfficialUnitIdToItself(): void
    {
        $unit = m::mock(Unit::class);
        $unit->shouldReceive('isOfficial')->andReturn(TRUE);

        $repository = m::mock(IUnitRepository::class);
        $repository->shouldReceive('find')
            ->with(5, TRUE)
            ->andReturn($unit);

        $resolver = new UnitResolver($repository);

        $this->assertSame(5, $resolver->getOfficialUnitId(5));
    }

    public function testResolveSubunitToOfficialUnitId(): void
    {
        $unit = m::mock(Unit::class);
        $unit->shouldReceive('isOfficial')->andReturn(FALSE);
        $unit->shouldReceive('getParentId')->andReturn(10);

        $officialParent = m::mock(Unit::class);
        $officialParent->shouldReceive('isOfficial')->andReturn(TRUE);

        $repository = m::mock(IUnitRepository::class);
        $repository->shouldReceive('find')
            ->with(5, TRUE)
            ->andReturn($unit);

        $repository->shouldReceive('find')
            ->with(10, TRUE)
            ->andReturn($officialParent);

        $resolver = new UnitResolver($repository);

        $this->assertSame(10, $resolver->getOfficialUnitId(5));
    }

}
