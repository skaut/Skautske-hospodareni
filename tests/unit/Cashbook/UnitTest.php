<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Codeception\Test\Unit as TestCase;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Events\Unit\CashbookWasCreated;
use Model\Cashbook\Exception\YearCashbookAlreadyExists;
use Model\Common\UnitId;
use Ramsey\Uuid\Uuid;

final class UnitTest extends TestCase
{
    public function testCreateWithInitialCashbook() : void
    {
        $id         = new UnitId(15);
        $cashbookId = CashbookId::generate();
        $year       = 2018;

        $unit = new Unit($id, $cashbookId, $year);

        $activeCashbook = $unit->getActiveCashbook();
        $this->assertSame($cashbookId, $activeCashbook->getCashbookId());
        $this->assertSame(1, $activeCashbook->getId());
        $this->assertSame($year, $activeCashbook->getYear());
        $this->assertSame([$activeCashbook], $unit->getCashbooks());

        $events = $unit->extractEventsToDispatch();
        $this->assertCount(1, $events);

        /** @var CashbookWasCreated $event */
        $event = $events[0];
        $this->assertInstanceOf(CashbookWasCreated::class, $event);
        $this->assertSame($id, $event->getUnitId());
        $this->assertSame($activeCashbook->getCashbookId(), $event->getCashbookId());
    }

    public function testCreateCashbook() : void
    {
        $id   = new UnitId(15);
        $unit = new Unit($id, CashbookId::generate(), 2018);
        $unit->extractEventsToDispatch(); // clear events

        $unit->createCashbook(2019);

        $this->assertCount(2, $unit->getCashbooks());

        $cashbook = $unit->getCashbooks()[1];
        $this->assertSame(2, $cashbook->getId());
        $this->assertSame(2019, $cashbook->getYear());
        $this->assertTrue(Uuid::isValid($cashbook->getCashbookId()->toString()), 'UUID cashbook ID is generated');

        $events = $unit->extractEventsToDispatch();
        $this->assertCount(1, $events);

        /** @var CashbookWasCreated $event */
        $event = $events[0];
        $this->assertInstanceOf(CashbookWasCreated::class, $event);
        $this->assertSame($id, $event->getUnitId());
        $this->assertSame($cashbook->getCashbookId(), $event->getCashbookId());
    }

    public function testCannotCreateCashbookWithDuplicateYear() : void
    {
        $unit = new Unit(new UnitId(15), CashbookId::generate(), 2018);

        $this->expectException(YearCashbookAlreadyExists::class);

        $unit->createCashbook(2018);
    }

    public function testActivateCashbook() : void
    {
        $unit = new Unit(new UnitId(15), CashbookId::generate(), 2018);
        $unit->createCashbook(2019);

        $unit->activateCashbook(2);

        $this->assertSame(2, $unit->getActiveCashbook()->getId());
    }

    public function testActivateCashbookForUnexistentCashbookThrowsException() : void
    {
        $unit = new Unit(new UnitId(15), CashbookId::generate(), 2018);

        $this->expectException(UnitCashbookNotFound::class);

        $unit->activateCashbook(2);
    }
}
