<?php

declare(strict_types=1);

namespace App\Model\Travel\Vehicle;

/** Hybrids count as combustion. */
enum FuelType: string
{
    case COMBUSTION = 'combustion';
    case ELECTRIC = 'electric';

    public function label(): string
    {
        return match ($this) {
            self::COMBUSTION => 'Spalovací pohon',
            self::ELECTRIC => 'Elektrický pohon',
        };
    }

    public function consumptionUnit(): string
    {
        return match ($this) {
            self::COMBUSTION => 'l/100 km',
            self::ELECTRIC => 'kWh/100 km',
        };
    }

    public function fuelPriceUnit(): string
    {
        return match ($this) {
            self::COMBUSTION => 'l',
            self::ELECTRIC => 'kWh',
        };
    }

    /** @return array<string, string> */
    public static function toSelect(): array
    {
        $options = [];
        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
