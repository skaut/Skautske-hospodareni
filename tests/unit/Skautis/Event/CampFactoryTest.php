<?php

declare(strict_types=1);

namespace App\Model\Skautis\Event;

use App\Model\Common\UnitId;
use App\Model\Event\SkautisCampId;
use App\Model\Skautis\Factory\CampFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use stdClass;

/**
 * Skautis vrací tábor jako stdClass s nepovinnými poli a účastnící se oddíly v podivném tvaru
 * (jeden string vs. pole stringů) — factory to musí zvládnout obojí.
 */
final class CampFactoryTest extends Unit
{
    public function testRealCampWithStatisticsAndSeveralTroops(): void
    {
        $camp = (new CampFactory())->create($this->skautisCamp([
            'RealAdult' => 6,
            'RealChild' => 24,
            'RealCount' => 30,
            'RealChildDays' => 336,
            'RealPersonDays' => 420,
            'TotalDays' => 14,
            'IsOnlineLogin' => true,
            'IsRealAutoComputed' => true,
            'IsRealTotalCostAutoComputed' => false,
            'ID_UnitArray' => (object) ['string' => ['101', '102']],
        ]));

        self::assertEquals(new SkautisCampId(21), $camp->getId());
        self::assertSame('Tábor Lhota', $camp->getDisplayName());
        self::assertEquals(new UnitId(123), $camp->getUnitId());
        self::assertSame('Středisko Test', $camp->getUnitName());
        self::assertSame('Lhota', $camp->getLocation());
        self::assertSame('real', $camp->getState());
        self::assertSame('123.45', $camp->getRegistrationNumber());
        self::assertSame(14, $camp->getTotalDays());
        self::assertEquals(ChronosDate::create(2026, 7, 1), $camp->getStartDate());
        self::assertEquals(ChronosDate::create(2026, 7, 14), $camp->getEndDate());
        self::assertEquals([new UnitId(101), new UnitId(102)], $camp->getParticipatingUnits());

        $statistics = $camp->getParticipantStatistics();
        self::assertNotNull($statistics);
        self::assertSame(6, $statistics->getRealAdult());
        self::assertSame(24, $statistics->getRealChild());
        self::assertSame(30, $statistics->getRealCount());
        self::assertSame(336, $statistics->getRealChildDays());
        self::assertSame(420, $statistics->getRealPersonDays());
    }

    public function testDraftCampWithoutOptionalFields(): void
    {
        $camp = (new CampFactory())->create($this->skautisCamp(['ID_EventCampState' => 'draft']));

        self::assertNull($camp->getParticipantStatistics(), 'bez RealPersonDays se statistika nevytváří');
        self::assertNull($camp->getTotalDays());
        self::assertSame([], $camp->getParticipatingUnits());
        self::assertSame('draft', $camp->getState());
    }

    public function testMissingChildDaysDefaultToZero(): void
    {
        $camp = (new CampFactory())->create($this->skautisCamp([
            'RealAdult' => 2,
            'RealChild' => 10,
            'RealCount' => 12,
            'RealPersonDays' => 120,
        ]));

        self::assertSame(0, $camp->getParticipantStatistics()?->getRealChildDays());
    }

    public function testSingleTroopArrivesAsPlainString(): void
    {
        $camp = (new CampFactory())->create($this->skautisCamp([
            'ID_UnitArray' => (object) ['string' => '101'],
        ]));

        self::assertEquals([new UnitId(101)], $camp->getParticipatingUnits());
    }

    public function testUnitArrayWithUnexpectedShapeIsIgnored(): void
    {
        $withoutStringKey = (new CampFactory())->create($this->skautisCamp([
            'ID_UnitArray' => (object) ['other' => '101'],
        ]));
        $withUnexpectedType = (new CampFactory())->create($this->skautisCamp([
            'ID_UnitArray' => (object) ['string' => 101],
        ]));

        self::assertSame([], $withoutStringKey->getParticipatingUnits());
        self::assertSame([], $withUnexpectedType->getParticipatingUnits(), 'int místo stringu se zahodí');
    }

    /** @param array<string, mixed> $overrides */
    private function skautisCamp(array $overrides = []): stdClass
    {
        return (object) ($overrides + [
            'ID' => 21,
            'DisplayName' => 'Tábor Lhota',
            'ID_Unit' => 123,
            'Unit' => 'Středisko Test',
            'StartDate' => '2026-07-01T00:00:00',
            'EndDate' => '2026-07-14T00:00:00',
            'Location' => 'Lhota',
            'ID_EventCampState' => 'real',
            'RegistrationNumber' => '123.45',
        ]);
    }
}
