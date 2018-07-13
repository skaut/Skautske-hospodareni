<?php

declare(strict_types=1);

namespace Model\Travel;

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
        $metadata = new Metadata(new \DateTimeImmutable(), 'František Maša');
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

        $this->expectException(\InvalidArgumentException::class);

        new Vehicle('test', $unit, $subunit, '666-666', 6.0, new Metadata(new \DateTimeImmutable(), 'František Maša'));
    }
}
