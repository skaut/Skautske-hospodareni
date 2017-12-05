<?php

namespace Model\Cashbook;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Events\ChitWasAdded;

class CashbookTest extends \Codeception\Test\Unit
{

    public function testCreateCashbook(): void
    {
        $type = CashbookType::get(CashbookType::EVENT);
        $cashbook = new Cashbook(100, $type);

        $this->assertSame(100, $cashbook->getId());
        $this->assertSame($type, $cashbook->getType());
    }

    public function testAddingChitRaisesEvent(): void
    {
        $cashbookId = 10;
        $cashbook = $this->createEventCashbook($cashbookId);
        $categoryId = 6;

        $cashbook->addChit(NULL, Date::now(), NULL, new Amount('500'), 'Nákup potravin', $categoryId);

        $events = $cashbook->extractEventsToDispatch();
        $this->assertCount(1, $events);

        /* @var $event ChitWasAdded */
        $event = $events[0];
        $this->assertInstanceOf(ChitWasAdded::class, $event);
        $this->assertSame($cashbookId, $event->getCashbookId());
        $this->assertSame($categoryId, $event->getCategoryId());
    }

    public function testGetCategoryTotalsReturnsCorrectValues(): void
    {
        $cashbook = $this->createEventCashbook(1);

        $addChit = function(int $categoryId, string $amount) use ($cashbook) {
            $cashbook->addChit(NULL, Date::now(), NULL, new Amount($amount), '', $categoryId);
        };

        $addChit(1, '200');
        $addChit(2, '100');
        $addChit(1, '300');
        $addChit(2, '150');

        $expectedTotals = [
            1 => 500.0,
            2 => 250.0,
        ];

        $totals = $cashbook->getCategoryTotals();

        ksort($expectedTotals);
        ksort($totals);

        $this->assertSame($expectedTotals, $totals);
    }

    public function testAddChitRaisesEvent(): void
    {
        $cashbook = $this->createEventCashbook(10);

        $cashbook->addChit(
            new Cashbook\ChitNumber('123'),
            new Date('2017-11-17'),
            new Cashbook\Recipient('František Maša'),
            new Cashbook\Amount('100'),
            'purpose',
            666
        );

        $events = $cashbook->extractEventsToDispatch();

        $this->assertCount(1, $events);
        /* @var $event ChitWasAdded */
        $event = $events[0];
        $this->assertInstanceOf(ChitWasAdded::class, $event);
        $this->assertSame(10, $event->getCashbookId());
        $this->assertSame(666, $event->getCategoryId());
    }

    private function createEventCashbook(int $cashbookId): Cashbook
    {
        return new Cashbook($cashbookId, CashbookType::get(CashbookType::EVENT));
    }

}
