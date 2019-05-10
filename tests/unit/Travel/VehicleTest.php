<?php

declare(strict_types=1);

namespace Model\Travel;

use DateTimeImmutable;
use InvalidArgumentException;
use Mockery as m;
use Model\Travel\Vehicle\Metadata;
use Model\Unit\Unit;

class VehicleTest extends \Codeception\Test\Unit
{
    public function testCreateWithSubunit() : void
    {
        $unit    = m::mock(Unit::class, ['getId' => 10]);
        $subunit = m::mock(Unit::class, ['getId' => 20]);
        $subunit->shouldReceive('isSubunitOf')->with($unit)->once()->andReturn(true);
        $metadata = new Metadata(new DateTimeImmutable(), 'František Maša');
        $vehicle  = new Vehicle('test', $unit, $subunit, '666-666', 6.0, $metadata);

        $this->assertSame(10, $vehicle->getUnitId());
        $this->assertSame(20, $vehicle->getSubunitId());
        $this->assertTrue($metadata->equals($vehicle->getMetadata()));
    }

    public function testCantCreateWithUnrelatedSubunit() : void
    {
        $unit    = m::mock(Unit::class, ['getId' => 10]);
        $subunit = m::mock(Unit::class, ['getId' => 20]);
        $subunit->shouldReceive('isSubunitOf')->with($unit)->once()->andReturn(false);

        $this->expectException(InvalidArgumentException::class);

        new Vehicle('test', $unit, $subunit, '666-666', 6.0, new Metadata(new DateTimeImmutable(), 'František Maša'));
    }

    public function testAddRoadworthyScan() : void
    {
        $unit    = m::mock(Unit::class, ['getId' => 10]);
        $vehicle = new Vehicle('Test', $unit, null, '333-333', 2, new Metadata(new DateTimeImmutable(), 'FM'));

        $path = 'roadworthy.jpg';

        $vehicle->addRoadworthyScan($path);

        $roadworthyScans = $vehicle->getRoadworthyScans();
        $this->assertCount(1, $roadworthyScans);
        $this->assertSame($path, $roadworthyScans[0]->getFilePath());
    }
}
