<?php

declare(strict_types=1);

namespace App\Model\Usage;

use function array_filter;
use function array_slice;
use function explode;
use function implode;
use function preg_match;
use function strlen;
use function substr;
use function trim;

/**
 * The unit the usage counts under, and the kraj and okres it belongs to.
 *
 * The code is the registration number of the unit of the role, cut to its first two parts: a troop 621.66.004
 * and its patrol count under the středisko 621.66, the roles of the okres under 621. The numbering follows
 * the Junák rules for marking units: kraje and okresy have three places, an okres starts with the first two
 * places of its kraj (Mělník 216 in the Středočeský kraj 210), a středisko adds two digits after a dot to the
 * number of its okres or kraj. Special units have 900.xx. The headquarters has its own code.
 */
final class UsageUnitCode
{
    /** The counters of the whole system, without a unit. */
    public const ALL = '';
    public const HEADQUARTERS = 'ustredi';
    public const UNKNOWN = 'neurceno';
    public const SPECIAL = '900';

    /** Junák kraje, they do not change. */
    public const REGIONS = [
        '110' => 'Kraj Praha',
        '210' => 'Středočeský kraj',
        '310' => 'Jihočeský kraj',
        '320' => 'Plzeňský kraj',
        '410' => 'Karlovarský kraj',
        '420' => 'Ústecký kraj',
        '510' => 'Liberecký kraj',
        '520' => 'Královéhradecký kraj',
        '530' => 'Pardubický kraj',
        '610' => 'Kraj Vysočina',
        '620' => 'Jihomoravský kraj TGM',
        '710' => 'Olomoucký kraj',
        '720' => 'Zlínský kraj',
        '810' => 'Moravskoslezský kraj',
    ];

    private const HEADQUARTERS_TYPE = 'ustredi';

    public static function fromUnit(string $registrationNumber, string $unitType): string
    {
        if ($unitType === self::HEADQUARTERS_TYPE) {
            return self::HEADQUARTERS;
        }

        $parts = array_filter(explode('.', trim($registrationNumber)), static fn (string $part): bool => trim($part) !== '');

        return $parts === [] ? self::UNKNOWN : implode('.', array_slice($parts, 0, 2));
    }

    /**
     * The okres of a code: its first part. For a role of the kraj or a středisko directly under the kraj it is
     * the kraj itself, for a special unit 900.
     */
    public static function districtOf(string $code): ?string
    {
        $district = explode('.', $code)[0];

        return self::isDistrict($district) ? $district : null;
    }

    /** The row of the whole system: a kraj, the special units, the headquarters, or unknown. */
    public static function regionOf(string $code): string
    {
        if ($code === self::HEADQUARTERS) {
            return self::HEADQUARTERS;
        }

        $district = self::districtOf($code);

        if ($district === null) {
            return self::UNKNOWN;
        }

        return $district === self::SPECIAL ? self::SPECIAL : substr($district, 0, 2).'0';
    }

    public static function isRegion(string $code): bool
    {
        return isset(self::REGIONS[$code]);
    }

    /**
     * An okres with units to show: three places that belong to a kraj, the kraj itself (its roles and the
     * střediska directly under it), or the special units.
     */
    public static function isDistrict(string $code): bool
    {
        if ($code === self::SPECIAL) {
            return true;
        }

        return strlen($code) === 3
            && preg_match('~^[0-9][0-9][0-9A-Za-z]$~', $code) === 1
            && self::isRegion(substr($code, 0, 2).'0');
    }

    public static function regionLabel(string $code): string
    {
        return match (true) {
            $code === self::HEADQUARTERS => 'Ústředí',
            $code === self::UNKNOWN => 'Neurčeno',
            $code === self::SPECIAL => '900 Zvláštní jednotky',
            default => $code.' '.(self::REGIONS[$code] ?? ''),
        };
    }

    /** A row of the okresy of a kraj: the kraj itself stands for its roles and its direct střediska. */
    public static function districtLabel(string $district): string
    {
        return self::isRegion($district) ? $district.' (kraj a jeho přímé jednotky)' : $district;
    }

    /** A row of the střediska of an okres: the okres itself stands for the roles of the okres. */
    public static function unitLabel(string $code): string
    {
        if ($code === self::SPECIAL) {
            return $code.' (role zvláštních jednotek)';
        }

        if (self::isRegion($code)) {
            return $code.' (role kraje)';
        }

        return self::isDistrict($code) ? $code.' (role okresu)' : $code;
    }
}
