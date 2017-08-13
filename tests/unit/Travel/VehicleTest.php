<?php


namespace Model\Travel;

use Mockery as m;
use Model\Unit\Unit;

class VehicleTest extends \Codeception\Test\Unit
{

    public function testCreateWithSubunit()
    {
        $unit = m::mock(Unit::class, ['getId' => 10]);
        $subunit = m::mock(Unit::class, ['getId' => 20]);
        $subunit->shouldReceive('isSubunitOf')->with($unit)->once()->andReturn(TRUE);

        $vehicle = new Vehicle('test', $unit, $subunit, '666-666', 6.0);

        $this->assertSame(10, $vehicle->getUnitId());
        $this->assertSame(20, $vehicle->getSubunitId());
    }


    public function testCantCreateWithUnrelatedSubunit()
    {
        $unit = m::mock(Unit::class, ['getId' => 10]);
        $subunit = m::mock(Unit::class, ['getId' => 20]);
        $subunit->shouldReceive('isSubunitOf')->with($unit)->once()->andReturn(FALSE);

        $this->expectException(\InvalidArgumentException::class);

        new Vehicle('test', $unit, $subunit, '666-666', 6.0);
    }
}
