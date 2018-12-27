<?php

declare(strict_types=1);

namespace Model\Unit\Services;

use Model\Unit\Unit;
use function sprintf;

class UnitTest extends \Codeception\Test\Unit
{
    public function testGetFullDisplayNameForOfficialUnit() : void
    {
        $unitName = 'Moje krásné středisko';
        $unit     = $this->createUnit($unitName, 'stredisko');

        $this->assertSame(sprintf('Junák - český skaut, %s, z. s.', $unitName), $unit->getFullDisplayName());
    }

    public function testGetFullDisplayNameForNonOfficialUnit() : void
    {
        $unitName = 'Muj oddíl';
        $unit     = $this->createUnit($unitName, 'oddil');

        $this->assertSame('', $unit->getFullDisplayName());
    }

    private function createUnit(string $unitName, string $type) : Unit
    {
        $regNumber = '123';
        return new Unit(
            1,
            sprintf('%s %s', $regNumber, $unitName),
            $unitName,
            '05596641',
            'Ulička 56',
            'Krno',
            '43267',
            $regNumber,
            $type,
            null,
            null
        );
    }
}
