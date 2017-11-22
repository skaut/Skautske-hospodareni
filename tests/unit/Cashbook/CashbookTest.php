<?php

namespace Model\Cashbook;

use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Events\ChitWasAdded;

class CashbookTest extends \Codeception\Test\Unit
{

    public function testAddingChitRaisesEvent(): void
    {
        $cashbookId = 10;
        $cashbook = new Cashbook($cashbookId);
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
        $cashbook = new Cashbook(1);

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

}
