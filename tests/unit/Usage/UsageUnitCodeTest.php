<?php

declare(strict_types=1);

namespace App\Model\Usage;

use Codeception\Test\Unit as TestCase;

use function usort;

final class UsageUnitCodeTest extends TestCase
{
    /** @return array<string, array{string, string, string}> */
    public static function units(): array
    {
        return [
            'troop counts under its středisko' => ['621.66.004', 'oddil', '621.66'],
            'patrol counts under its středisko' => ['621.66.004.01', 'druzina', '621.66'],
            'středisko' => ['621.66', 'stredisko', '621.66'],
            'okres keeps its own code' => ['621', 'okres', '621'],
            'kraj keeps its own code' => ['620', 'kraj', '620'],
            'headquarters has its own row' => ['', 'ustredi', UsageUnitCode::HEADQUARTERS],
            'unit without a number' => ['  ', 'stredisko', UsageUnitCode::UNKNOWN],
        ];
    }

    /** @dataProvider units */
    public function testCodeHasAtMostTwoPartsOfTheRegistrationNumber(string $registrationNumber, string $type, string $expected): void
    {
        self::assertSame($expected, UsageUnitCode::fromUnit($registrationNumber, $type));
    }

    /** @return array<string, array{string, string}> */
    public static function regions(): array
    {
        return [
            'středisko of an okres' => ['621.66', '620'],
            'okres' => ['622', '620'],
            'kraj' => ['620', '620'],
            'středisko directly under the kraj' => ['620.01', '620'],
            'okres with a letter' => ['21A.03', '210'],
            'Praha' => ['111.05', '110'],
            'special unit' => ['900.12', UsageUnitCode::SPECIAL],
            'headquarters' => [UsageUnitCode::HEADQUARTERS, UsageUnitCode::HEADQUARTERS],
            'not a kraj' => ['999.01', UsageUnitCode::UNKNOWN],
            'unknown' => [UsageUnitCode::UNKNOWN, UsageUnitCode::UNKNOWN],
        ];
    }

    /** @dataProvider regions */
    public function testKrajFollowsTheFirstTwoPlacesOfTheOkres(string $code, string $expected): void
    {
        self::assertSame($expected, UsageUnitCode::regionOf($code));
    }

    public function testOkresIsTheFirstPart(): void
    {
        self::assertSame('621', UsageUnitCode::districtOf('621.66'));
        self::assertSame('620', UsageUnitCode::districtOf('620.01'));
        self::assertSame('900', UsageUnitCode::districtOf('900.12'));
        self::assertNull(UsageUnitCode::districtOf(UsageUnitCode::HEADQUARTERS));
        self::assertNull(UsageUnitCode::districtOf('999.01'));
    }

    public function testOnlyJunakKrajeAndTheirOkresyCanBeOpened(): void
    {
        self::assertTrue(UsageUnitCode::isRegion('620'));
        self::assertFalse(UsageUnitCode::isRegion('621'));
        self::assertFalse(UsageUnitCode::isRegion(UsageUnitCode::SPECIAL));
        self::assertTrue(UsageUnitCode::isDistrict('621'));
        self::assertTrue(UsageUnitCode::isDistrict('620'));
        self::assertTrue(UsageUnitCode::isDistrict(UsageUnitCode::SPECIAL));
        self::assertFalse(UsageUnitCode::isDistrict('621.66'));
        self::assertFalse(UsageUnitCode::isDistrict('62%'));
        self::assertFalse(UsageUnitCode::isDistrict('991'));
    }

    public function testRowsFollowTheNumbersOfTheUnits(): void
    {
        $codes = ['neurceno', '900', '620', '621.66', UsageUnitCode::HEADQUARTERS, '110', '621', '621.01', '62A', '210'];
        usort($codes, UsageUnitCode::compare(...));

        self::assertSame([UsageUnitCode::HEADQUARTERS, '110', '210', '620', '621', '621.01', '621.66', '62A', '900', 'neurceno'], $codes);
    }

    public function testLabelsSayWhatARowIs(): void
    {
        self::assertSame('620 Jihomoravský kraj TGM', UsageUnitCode::regionLabel('620'));
        self::assertSame('Ústředí', UsageUnitCode::regionLabel(UsageUnitCode::HEADQUARTERS));
        self::assertSame('620 (kraj a jeho přímé jednotky)', UsageUnitCode::districtLabel('620'));
        self::assertSame('621', UsageUnitCode::districtLabel('621'));
        self::assertSame('621 (role okresu)', UsageUnitCode::unitLabel('621'));
        self::assertSame('620 (role kraje)', UsageUnitCode::unitLabel('620'));
        self::assertSame('621.66', UsageUnitCode::unitLabel('621.66'));
    }
}
