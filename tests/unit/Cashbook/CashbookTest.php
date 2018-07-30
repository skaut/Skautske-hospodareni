<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Mockery as m;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Events\ChitWasAdded;
use function ksort;

class CashbookTest extends Unit
{
    public function testCreateCashbook() : void
    {
        $type       = CashbookType::get(CashbookType::EVENT);
        $cashbookId = CashbookId::fromInt(100);

        $cashbook = new Cashbook($cashbookId, $type);

        $this->assertTrue($cashbookId->equals($cashbook->getId()));
        $this->assertSame($type, $cashbook->getType());
    }

    public function testAddingChitRaisesEvent() : void
    {
        $cashbookId = CashbookId::fromInt(10);
        $cashbook   = $this->createEventCashbook($cashbookId);
        $category   = $this->mockCategory(6);

        $cashbook->addChit(new ChitBody(null, Date::now(), null, new Amount('500'), 'Nákup potravin'), $category);

        $events = $cashbook->extractEventsToDispatch();
        $this->assertCount(1, $events);

        /** @var ChitWasAdded $event */
        $event = $events[0];
        $this->assertInstanceOf(ChitWasAdded::class, $event);
        $this->assertTrue($cashbookId->equals($event->getCashbookId()));
        $this->assertSame(6, $event->getCategoryId());
    }

    public function testGetCategoryTotalsReturnsCorrectValues() : void
    {
        $cashbook = $this->createEventCashbook();

        $addChit = function(int $categoryId, string $amount) use ($cashbook) {
            $chitBody = new ChitBody(null, new Date(), null, new Amount($amount), '');
            $cashbook->addChit($chitBody, $this->mockCategory($categoryId));
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

    public function testAddChitRaisesEvent() : void
    {
        $cashbookId = CashbookId::fromInt(10);

        $cashbook = $this->createEventCashbook($cashbookId);

        $recipient = new Recipient('František Maša');
        $chitBody = new ChitBody(new ChitNumber('123'), new Date(), $recipient, new Amount('100'), 'Nákup potravin');

        $cashbook->addChit($chitBody, $this->mockCategory(666));

        $events = $cashbook->extractEventsToDispatch();

        $this->assertCount(1, $events);
        /** @var ChitWasAdded $event */
        $event = $events[0];
        $this->assertInstanceOf(ChitWasAdded::class, $event);
        $this->assertTrue($cashbookId->equals($event->getCashbookId()));
        $this->assertSame(666, $event->getCategoryId());
    }

    /**
     * @dataProvider dataValidChitNumberPrefixes
     */
    public function testUpdateChitNumberPrefix(?string $prefix) : void
    {
        $cashbook = $this->createEventCashbook();

        $this->assertNull($cashbook->getChitNumberPrefix());

        $cashbook->updateChitNumberPrefix($prefix);

        $this->assertSame($prefix, $cashbook->getChitNumberPrefix());
    }

    public function dataValidChitNumberPrefixes() : array
    {
        return [
            ['test'],
            [null],
        ];
    }

    public function testClearCashbook() : void
    {
        $cashbook = new Cashbook(CashbookId::fromInt(11), CashbookType::get(CashbookType::EVENT));
        $chitBody = new ChitBody(null, new Date(), null, new Amount('100'), 'Účastnické poplatky');

        for ($i = 0; $i < 5; $i++) {
            $cashbook->addChit($chitBody, $this->mockCategory(666));
        }

        $cashbook->clear();

        $this->assertEmpty($cashbook->getChits());
    }

    public function testUpdateNote() : void
    {
        $note     = 'moje poznamka';
        $cashbook = $this->createEventCashbook();
        $this->assertEmpty($cashbook->getNote());
        $cashbook->updateNote($note);
        $this->assertSame($note, $cashbook->getNote());
    }

    private function createEventCashbook(?CashbookId $cashbookId = null) : Cashbook
    {
        return new Cashbook($cashbookId ?? CashbookId::fromInt(1), CashbookType::get(CashbookType::EVENT));
    }

    private function mockCategory(int $id) : ICategory
    {
        return m::mock(ICategory::class, [
            'getId' => $id,
            'getOperationType' => Operation::get(Operation::INCOME),
        ]);
    }
}
