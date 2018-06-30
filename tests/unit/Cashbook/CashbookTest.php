<?php

namespace Model\Cashbook;

use Cake\Chronos\Date;
use Mockery as m;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Events\ChitWasAdded;

class CashbookTest extends \Codeception\Test\Unit
{

    public function testCreateCashbook(): void
    {
        $type = CashbookType::get(CashbookType::EVENT);
        $cashbookId = CashbookId::fromInt(100);

        $cashbook = new Cashbook($cashbookId, $type);

        $this->assertTrue($cashbookId->equals($cashbook->getId()));
        $this->assertSame($type, $cashbook->getType());
    }

    public function testAddingChitRaisesEvent(): void
    {
        $cashbookId = CashbookId::fromInt(10);
        $cashbook = $this->createEventCashbook($cashbookId);
        $category = $this->mockCategory(6);

        $cashbook->addChit(NULL, Date::now(), NULL, new Amount('500'), 'Nákup potravin', $category);

        $events = $cashbook->extractEventsToDispatch();
        $this->assertCount(1, $events);

        /* @var $event ChitWasAdded */
        $event = $events[0];
        $this->assertInstanceOf(ChitWasAdded::class, $event);
        $this->assertTrue($cashbookId->equals($event->getCashbookId()));
        $this->assertSame(6, $event->getCategoryId());
    }

    public function testGetCategoryTotalsReturnsCorrectValues(): void
    {
        $cashbook = $this->createEventCashbook();

        $addChit = function(int $categoryId, string $amount) use ($cashbook) {
            $cashbook->addChit(NULL, Date::now(), NULL, new Amount($amount), '', $this->mockCategory($categoryId));
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
        $cashbookId = CashbookId::fromInt(10);

        $cashbook = $this->createEventCashbook($cashbookId);

        $cashbook->addChit(
            new Cashbook\ChitNumber('123'),
            new Date('2017-11-17'),
            new Cashbook\Recipient('František Maša'),
            new Cashbook\Amount('100'),
            'purpose',
            $this->mockCategory(666)
        );

        $events = $cashbook->extractEventsToDispatch();

        $this->assertCount(1, $events);
        /* @var $event ChitWasAdded */
        $event = $events[0];
        $this->assertInstanceOf(ChitWasAdded::class, $event);
        $this->assertTrue($cashbookId->equals($event->getCashbookId()));
        $this->assertSame(666, $event->getCategoryId());
    }

    /**
     * @dataProvider dataValidChitNumberPrefixes
     */
    public function testUpdateChitNumberPrefix(?string $prefix): void
    {
        $cashbook = $this->createEventCashbook();

        $this->assertNull($cashbook->getChitNumberPrefix());

        $cashbook->updateChitNumberPrefix($prefix);

        $this->assertSame($prefix, $cashbook->getChitNumberPrefix());
    }

    public function dataValidChitNumberPrefixes(): array
    {
        return [
            ['test'],
            [NULL],
        ];
    }

    public function testClearCashbook(): void
    {
        $cashbook = new Cashbook(CashbookId::fromInt(11), CashbookType::get(CashbookType::EVENT));

        for ($i = 0; $i < 5; $i++) {
            $cashbook->addChit(
                NULL,
                new Date('2017-11-17'),
                NULL,
                new Cashbook\Amount('100'),
                'purpose',
                $this->mockCategory(666)
            );
        }

        $cashbook->clear();

        $this->assertEmpty($cashbook->getChits());
    }

    private function createEventCashbook(?CashbookId $cashbookId = NULL): Cashbook
    {
        return new Cashbook($cashbookId ?? CashbookId::fromInt(1), CashbookType::get(CashbookType::EVENT));
    }

    private function mockCategory(int $id): ICategory
    {
        return m::mock(ICategory::class, [
            'getId' => $id,
            'getOperationType' => Operation::get(Operation::INCOME),
        ]);
    }

}
