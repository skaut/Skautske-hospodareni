<?php

declare(strict_types=1);

namespace App\Model\Travel\Vehicle;

use Codeception\Test\Unit;

class FuelTypeTest extends Unit
{
    /** @dataProvider provideFuelTypes */
    public function testProvidesLabelAndUnits(FuelType $fuelType, string $label, string $consumptionUnit, string $fuelPriceUnit): void
    {
        $this->assertSame($label, $fuelType->label());
        $this->assertSame($consumptionUnit, $fuelType->consumptionUnit());
        $this->assertSame($fuelPriceUnit, $fuelType->fuelPriceUnit());
    }

    /** @return array<string, array{FuelType, string, string, string}> */
    public static function provideFuelTypes(): array
    {
        return [
            'combustion' => [FuelType::COMBUSTION, 'Spalovací pohon', 'l/100 km', 'l'],
            'electric' => [FuelType::ELECTRIC, 'Elektrický pohon', 'kWh/100 km', 'kWh'],
        ];
    }

    public function testSelectOffersCombustionFirst(): void
    {
        $this->assertSame(
            ['combustion' => 'Spalovací pohon', 'electric' => 'Elektrický pohon'],
            FuelType::toSelect(),
        );
    }
}
