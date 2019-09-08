<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Assert\InvalidArgumentException;
use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Helpers;
use Mockery as m;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\Chit\DuplicitCategory;
use Model\Cashbook\Cashbook\Chit\SingleItemRestriction;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitItem;
use Model\Cashbook\Cashbook\PaymentMethod;
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

        Helpers::addChitToCashbook($cashbook, null, null, null, null);

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

        Helpers::addChitToCashbook($cashbook, null, null, 1, '200');
        Helpers::addChitToCashbook($cashbook, null, null, 2, '100');
        Helpers::addChitToCashbook($cashbook, null, null, 1, '300');
        Helpers::addChitToCashbook($cashbook, null, null, 2, '150');

        $chitBody = new ChitBody(null, new Date(), null);
        $items    = [
            new Cashbook\ChitItem(new Amount('35'), Helpers::mockChitItemCategory(1), 'čokoláda'),
            new Cashbook\ChitItem(new Amount('75'), Helpers::mockChitItemCategory(2), 'vlak'),
        ];

        $categories = [
            1 => m::mock(Category::class, ['getId' => 1, 'getOperationType' => Operation::EXPENSE(), 'isVirtual' => false]),
            2 => m::mock(Category::class, ['getId' => 2, 'getOperationType' => Operation::EXPENSE(), 'isVirtual' => false]),
        ];

        $cashbook->addChit(
            $chitBody,
            PaymentMethod::CASH(),
            $items,
            $categories
        );

        $expectedTotals = [
            1 => 535.0,
            2 => 325.0,
        ];

        $totals = $cashbook->getCategoryTotals();

        ksort($expectedTotals);
        ksort($totals);

        $this->assertSame($expectedTotals, $totals);
    }

    public function testGetCategoryTotalsCountCorrectlyIncome() : void
    {
        $cashbook = $this->createEventCashbook();

        Helpers::addChitToCashbook($cashbook, null, null, ICategory::CATEGORY_PARTICIPANT_INCOME_ID, '200');
        Helpers::addChitToCashbook($cashbook, null, null, ICategory::CATEGORY_PARTICIPANT_INCOME_ID, '300');
        Helpers::addChitToCashbook($cashbook, null, null, ICategory::CATEGORY_REFUND_ID, '50');
        Helpers::addChitToCashbook($cashbook, null, null, ICategory::CATEGORY_REFUND_ID, '50');

        $expectedTotals = [ICategory::CATEGORY_PARTICIPANT_INCOME_ID => 500.0, ICategory::CATEGORY_REFUND_ID => 100.0];

        $totals = $cashbook->getCategoryTotals();

        ksort($expectedTotals);
        ksort($totals);

        $this->assertSame($expectedTotals, $totals);
    }

    public function testAddChitRaisesEvent() : void
    {
        $cashbookId = CashbookId::fromString('10');

        $cashbook = $this->createEventCashbook($cashbookId);

        Helpers::addChitToCashbook($cashbook, null, null, null, null);

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

        for ($i = 0; $i < 5; $i++) {
            Helpers::addChitToCashbook($cashbook, null, null, null, null);
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

    public function testGenerateChitNumbersMaxNotFound() : void
    {
        $cashbook = $this->createEventCashbook();
        Helpers::addChitToCashbook($cashbook, null, PaymentMethod::CASH());
        $this->expectException(MaxChitNumberNotFound::class);
        $cashbook->generateChitNumbers(PaymentMethod::CASH());
    }

    public function testCreateChitWithoutItems() : void
    {
        $cashbook = $this->createEventCashbook();
        $chitBody = new ChitBody(null, new Date(), null);
        $this->expectException(InvalidArgumentException::class);
        $cashbook->addChit($chitBody, PaymentMethod::CASH(), [], []);
    }

    public function testCreateChitWithDuplicitItemCategory() : void
    {
        $cashbook   = $this->createEventCashbook();
        $chitBody   = new ChitBody(null, new Date(), null);
        $categoryId = 1;
        $category   = new \Model\Cashbook\Cashbook\Category($categoryId, Operation::INCOME());
        $items      = [
            new ChitItem(new Amount('100'), $category, ''),
            new ChitItem(new Amount('100'), $category, ''),
        ];
        $this->expectException(DuplicitCategory::class);
        $cashbook->addChit($chitBody, PaymentMethod::CASH(), $items, Helpers::mockCashbookCategories($categoryId));
    }

    public function testCreateChitWithVirtualCategory() : void
    {
        $cashbook = $this->createEventCashbook();
        $chitBody = new ChitBody(null, new Date(), null);

        $categories =  [
            1 => m::mock(Category::class, ['getId' => 1, 'getOperationType' => Operation::EXPENSE(), 'isVirtual' => true]),
            2 => m::mock(Category::class, ['getId' => 2, 'getOperationType' => Operation::EXPENSE(), 'isVirtual' => false]),
        ];

        $category1 = Helpers::mockChitItemCategory(1);
        $category2 = Helpers::mockChitItemCategory(2);
        $items     = [
            new ChitItem(new Amount('100'), $category1, ''),
            new ChitItem(new Amount('100'), $category2, ''),
        ];
        $this->expectException(SingleItemRestriction::class);
        $cashbook->addChit($chitBody, PaymentMethod::CASH(), $items, $categories);
    }

    private function createEventCashbook(?CashbookId $cashbookId = null) : Cashbook
    {
        return new Cashbook($cashbookId ?? CashbookId::fromString('1'), CashbookType::get(CashbookType::EVENT));
    }
}
