<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Helpers;
use Mockery as m;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Events\ChitWasAdded;
use function ksort;

class CashbookTest extends Unit
{
    public function testCreateCashbook() : void
    {
        $type       = CashbookType::get(CashbookType::EVENT);
        $cashbookId = CashbookId::fromString('100');

        $cashbook = new Cashbook($cashbookId, $type);

        $this->assertTrue($cashbookId->equals($cashbook->getId()));
        $this->assertSame($type, $cashbook->getType());
    }

    public function testAddingChitRaisesEvent() : void
    {
        $cashbookId = CashbookId::fromString('10');
        $cashbook   = $this->createEventCashbook($cashbookId);
        $category   = $this->mockCategory(6);

        $cashbook->addChit(
            new ChitBody(null, Date::now(), null),
            new Amount('500'),
            $category,
            PaymentMethod::CASH(),
            'Nákup potravin'
        );

        $events = $cashbook->extractEventsToDispatch();
        $this->assertCount(1, $events);

        /** @var ChitWasAdded $event */
        $event = $events[0];
        $this->assertInstanceOf(ChitWasAdded::class, $event);
        $this->assertTrue($cashbookId->equals($event->getCashbookId()));
    }

    public function testGetCategoryTotalsReturnsCorrectValues() : void
    {
        $cashbook = $this->createEventCashbook();

        $addChit = function (int $categoryId, string $amount) use ($cashbook) : void {
            $chitBody = new ChitBody(null, new Date(), null);
            $cashbook->addChit($chitBody, new Amount($amount), $this->mockCategory($categoryId), PaymentMethod::CASH(), '');
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

    public function testGetCategoryTotalsCountCorrectlyIncome() : void
    {
        $cashbook = $this->createEventCashbook();

        $addChit = function (int $categoryId, string $amount) use ($cashbook) : void {
            $chitBody = new ChitBody(null, new Date(), null);
            $cashbook->addChit($chitBody, new Amount($amount), $this->mockCategory($categoryId), PaymentMethod::CASH(), '');
        };

        $addChit(ICategory::CATEGORY_PARTICIPANT_INCOME_ID, '200');
        $addChit(ICategory::CATEGORY_PARTICIPANT_INCOME_ID, '300');
        $addChit(ICategory::CATEGORY_REFUND_ID, '50');
        $addChit(ICategory::CATEGORY_HPD_ID, '100');

        $expectedTotals = [ICategory::CATEGORY_PARTICIPANT_INCOME_ID => 550.0];

        $totals = $cashbook->getCategoryTotals();

        ksort($expectedTotals);
        ksort($totals);

        $this->assertSame($expectedTotals, $totals);
    }

    public function testAddChitRaisesEvent() : void
    {
        $cashbookId = CashbookId::fromString('10');

        $cashbook = $this->createEventCashbook($cashbookId);

        $recipient = new Recipient('František Maša');
        $chitBody  = new ChitBody(new ChitNumber('123'), new Date(), $recipient);

        $cashbook->addChit($chitBody, new Amount('100'), $this->mockCategory(666), PaymentMethod::CASH(), 'Nákup potravin');

        $events = $cashbook->extractEventsToDispatch();

        $this->assertCount(1, $events);
        /** @var ChitWasAdded $event */
        $event = $events[0];
        $this->assertInstanceOf(ChitWasAdded::class, $event);
        $this->assertTrue($cashbookId->equals($event->getCashbookId()));
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

    /**
     * @return mixed[]
     */
    public function dataValidChitNumberPrefixes() : array
    {
        return [
            ['test'],
            [null],
        ];
    }

    public function testClearCashbook() : void
    {
        $cashbook = $this->createEventCashbook();
        $chitBody = new ChitBody(null, new Date(), null);

        for ($i = 0; $i < 5; $i++) {
            $cashbook->addChit($chitBody, new Amount('100'), $this->mockCategory(666), PaymentMethod::CASH(), 'Účastnické poplatky');
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

    public function testHasOnlyNumericChitNumbers() : void
    {
        $cashbook = $this->createEventCashbook();
        Helpers::addChitToCashbook($cashbook, '1', PaymentMethod::CASH());
        Helpers::addChitToCashbook($cashbook, null, PaymentMethod::CASH());
        $this->assertTrue($cashbook->hasOnlyNumericChitNumbers());
        Helpers::addChitToCashbook($cashbook, 'V1', PaymentMethod::CASH());
        $this->assertFalse($cashbook->hasOnlyNumericChitNumbers());
    }

    public function testGenerateChitNumbers() : void
    {
        $cashbook = $this->createEventCashbook();
        Helpers::addChitToCashbook($cashbook, null, PaymentMethod::CASH());
        Helpers::addChitToCashbook($cashbook, null, PaymentMethod::BANK());
        Helpers::addChitToCashbook($cashbook, '1', PaymentMethod::CASH());
        $cashbook->generateChitNumbers(PaymentMethod::CASH());
        $this->assertSame('2', $cashbook->getChits()[0]->getBody()->getNumber()->toString());
        $this->assertNull($cashbook->getChits()[1]->getBody()->getNumber());
    }

    public function testGenerateChitNumbersMaxNotFound() : void
    {
        $cashbook = $this->createEventCashbook();
        Helpers::addChitToCashbook($cashbook, null, PaymentMethod::CASH());
        $this->expectException(MaxChitNumberNotFound::class);
        $cashbook->generateChitNumbers(PaymentMethod::CASH());
    }

    public function testAddBankChitWithCentsToCashbook() : void
    {
        $cashbook = $this->createEventCashbook();

        Helpers::addChitToCashbook($cashbook, null, PaymentMethod::BANK(), '100.25');
    }

    public function testAddingCashChitWithCentsToCashbookThrowsException() : void
    {
        $cashbook = $this->createEventCashbook();

        $this->expectException(InvalidAmount::class);
        $this->expectExceptionMessage('Chits paid by cash cannot must be rounded to whole CZK, 100.25 CZK given');

        Helpers::addChitToCashbook($cashbook, null, PaymentMethod::CASH(), '100.25');
    }

    private function createEventCashbook(?CashbookId $cashbookId = null) : Cashbook
    {
        return new Cashbook($cashbookId ?? CashbookId::fromString('1'), CashbookType::get(CashbookType::EVENT));
    }

    private function mockCategory(int $id) : ICategory
    {
        return m::mock(ICategory::class, [
            'getId' => $id,
            'getOperationType' => Operation::get(Operation::INCOME),
        ]);
    }
}
