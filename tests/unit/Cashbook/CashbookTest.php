<?php

declare(strict_types=1);

namespace App\Model\Cashbook;

use App\Model\Cashbook\Cashbook\Amount;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Cashbook\Chit\DuplicitCategory;
use App\Model\Cashbook\Cashbook\Chit\SingleItemRestriction;
use App\Model\Cashbook\Cashbook\ChitBody;
use App\Model\Cashbook\Cashbook\ChitItem;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\Events\ChitWasAdded;
use App\Model\Utils\MoneyFactory;
use Assert\InvalidArgumentException;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Helpers;
use Mockery as m;
use ReflectionClass;

use function assert;
use function ksort;

class CashbookTest extends Unit
{
    public function testCreateCashbook(): void
    {
        $type = CashbookType::get(CashbookType::EVENT);
        $cashbookId = CashbookId::generate();

        $cashbook = new Cashbook($cashbookId, $type);

        $this->assertTrue($cashbookId->equals($cashbook->getId()));
        $this->assertSame($type, $cashbook->getType());
    }

    public function testAddingChitRaisesEvent(): void
    {
        $cashbookId = CashbookId::generate();
        $cashbook = $this->createEventCashbook($cashbookId);

        Helpers::addChitToCashbook($cashbook, null, null, null, null);

        $events = $cashbook->extractEventsToDispatch();
        $this->assertCount(1, $events);

        $event = $events[0];
        assert($event instanceof ChitWasAdded);
        $this->assertInstanceOf(ChitWasAdded::class, $event);
        $this->assertTrue($cashbookId->equals($event->getCashbookId()));
    }

    public function testGetCategoryTotalsReturnsCorrectValues(): void
    {
        $cashbook = $this->createEventCashbook();

        Helpers::addChitToCashbook($cashbook, null, null, 1, '200');
        Helpers::addChitToCashbook($cashbook, null, null, 2, '100');
        Helpers::addChitToCashbook($cashbook, null, null, 1, '300');
        Helpers::addChitToCashbook($cashbook, null, null, 2, '150');

        $chitBody = new ChitBody(null, new ChronosDate(), null);
        $items = [
            new ChitItem(new Amount('35'), Helpers::mockChitItemCategory(1), 'čokoláda'),
            new ChitItem(new Amount('75'), Helpers::mockChitItemCategory(2), 'vlak'),
        ];

        $categories = [
            1 => m::mock(Category::class, ['getId' => 1, 'getOperationType' => Operation::EXPENSE(), 'isVirtual' => false]),
            2 => m::mock(Category::class, ['getId' => 2, 'getOperationType' => Operation::EXPENSE(), 'isVirtual' => false]),
        ];

        $cashbook->addChit(
            $chitBody,
            PaymentMethod::CASH(),
            $items,
            $categories,
        );

        $expectedTotals = [
            1 => MoneyFactory::fromDecimal('535.00'),
            2 => MoneyFactory::fromDecimal('325.00'),
        ];

        $totals = $cashbook->getCategoryTotals();

        ksort($expectedTotals);
        ksort($totals);

        foreach ($expectedTotals as $categoryId => $expectedTotal) {
            $this->assertTrue($expectedTotal->equals($totals[$categoryId]));
        }
    }

    public function testGetCategoryTotalsCountCorrectlyIncome(): void
    {
        $cashbook = $this->createEventCashbook();

        Helpers::addChitToCashbook($cashbook, null, null, ICategory::CATEGORY_PARTICIPANT_INCOME_ID, '200');
        Helpers::addChitToCashbook($cashbook, null, null, ICategory::CATEGORY_PARTICIPANT_INCOME_ID, '300');
        Helpers::addChitToCashbook($cashbook, null, null, ICategory::CATEGORY_REFUND_ID, '50');
        Helpers::addChitToCashbook($cashbook, null, null, ICategory::CATEGORY_REFUND_ID, '50');

        $expectedTotals = [
            ICategory::CATEGORY_PARTICIPANT_INCOME_ID => MoneyFactory::fromDecimal('500.00'),
            ICategory::CATEGORY_REFUND_ID => MoneyFactory::fromDecimal('100.00'),
        ];

        $totals = $cashbook->getCategoryTotals();

        ksort($expectedTotals);
        ksort($totals);

        foreach ($expectedTotals as $categoryId => $expectedTotal) {
            $this->assertTrue($expectedTotal->equals($totals[$categoryId]));
        }
    }

    public function testCopyChitsKeepsCompatibleCategoryWithTargetSpecificId(): void
    {
        $source = $this->createEventCashbook();
        $sourceCategory = new Category(6, 'Materiál', 'material', Operation::EXPENSE(), [], false, 100);
        $source->addChit(
            new ChitBody(null, new ChronosDate(), null),
            PaymentMethod::CASH(),
            [new ChitItem(new Amount('100'), new Cashbook\Category(6, Operation::EXPENSE()), 'materiál')],
            [6 => $sourceCategory],
        );
        $this->assignFirstChitIdentity($source);

        $target = new Cashbook(CashbookId::generate(), CashbookType::get(CashbookType::CAMP));
        $targetCategory = new CampCategory(501, Operation::EXPENSE(), 'Materiál', MoneyFactory::zero());

        $target->copyChitsFrom([1], $source, [$sourceCategory], [$targetCategory]);

        $this->assertArrayHasKey(501, $target->getCategoryTotals());
        $this->assertTrue(MoneyFactory::fromDecimal('100.00')->equals($target->getCategoryTotals()[501]));
    }

    public function testCopyChitsUsesUndefinedCategoryForUnknownCategory(): void
    {
        $source = $this->createEventCashbook();
        $sourceCategory = new CampCategory(501, Operation::EXPENSE(), 'Vlastní táborová položka', MoneyFactory::zero());
        $source->addChit(
            new ChitBody(null, new ChronosDate(), null),
            PaymentMethod::CASH(),
            [new ChitItem(new Amount('100'), new Cashbook\Category(501, Operation::EXPENSE()), 'historická položka')],
            [501 => $sourceCategory],
        );
        $this->assignFirstChitIdentity($source);

        $target = new Cashbook(CashbookId::generate(), CashbookType::get(CashbookType::CAMP));
        $undefinedCategory = new Category(ICategory::UNDEFINED_EXPENSE_ID, 'Neurčeno', 'undefined-expense', Operation::EXPENSE(), [], false, 100);

        $target->copyChitsFrom([1], $source, [$sourceCategory], [$undefinedCategory]);

        $this->assertArrayHasKey(ICategory::UNDEFINED_EXPENSE_ID, $target->getCategoryTotals());
    }

    public function testAddChitRaisesEvent(): void
    {
        $cashbookId = CashbookId::generate();

        $cashbook = $this->createEventCashbook($cashbookId);

        Helpers::addChitToCashbook($cashbook, null, null, null, null);

        $events = $cashbook->extractEventsToDispatch();

        $this->assertCount(1, $events);
        $event = $events[0];
        assert($event instanceof ChitWasAdded);
        $this->assertInstanceOf(ChitWasAdded::class, $event);
        $this->assertTrue($cashbookId->equals($event->getCashbookId()));
    }

    /** @dataProvider dataValidChitNumberPrefixes */
    public function testUpdateChitNumberPrefix(?string $prefix): void
    {
        $cashbook = $this->createEventCashbook();

        $this->assertNull($cashbook->getCashChitNumberPrefix());

        $cashbook->updateChitNumberPrefix($prefix, PaymentMethod::CASH());

        $this->assertSame($prefix, $cashbook->getCashChitNumberPrefix());
    }

    /** @return mixed[] */
    public function dataValidChitNumberPrefixes(): array
    {
        return [
            ['test'],
            [null],
        ];
    }

    public function testClearCashbook(): void
    {
        $cashbook = $this->createEventCashbook();

        for ($i = 0; $i < 5; ++$i) {
            Helpers::addChitToCashbook($cashbook, null, null, null, null);
        }

        $cashbook->clear();

        $this->assertEmpty($cashbook->getChits());
    }

    public function testUpdateNote(): void
    {
        $note = 'moje poznamka';
        $cashbook = $this->createEventCashbook();
        $this->assertEmpty($cashbook->getNote());
        $cashbook->updateNote($note);
        $this->assertSame($note, $cashbook->getNote());
    }

    public function testHasOnlyNumericChitNumbers(): void
    {
        $cashbook = $this->createEventCashbook();
        Helpers::addChitToCashbook($cashbook, '1', PaymentMethod::CASH());
        Helpers::addChitToCashbook($cashbook, null, PaymentMethod::CASH());
        $this->assertTrue($cashbook->hasOnlyNumericChitNumbers(PaymentMethod::CASH()));
        Helpers::addChitToCashbook($cashbook, 'V1', PaymentMethod::CASH());
        $this->assertFalse($cashbook->hasOnlyNumericChitNumbers(PaymentMethod::CASH()));
    }

    public function testGenerateChitNumbersMaxNotFound(): void
    {
        $cashbook = $this->createEventCashbook();
        Helpers::addChitToCashbook($cashbook, null, PaymentMethod::CASH());
        $this->expectException(MaxChitNumberNotFound::class);
        $cashbook->generateChitNumbers(PaymentMethod::CASH());
    }

    public function testCreateChitWithoutItems(): void
    {
        $cashbook = $this->createEventCashbook();
        $chitBody = new ChitBody(null, new ChronosDate(), null);
        $this->expectException(InvalidArgumentException::class);
        $cashbook->addChit($chitBody, PaymentMethod::CASH(), [], []);
    }

    public function testCreateChitWithDuplicitItemCategory(): void
    {
        $cashbook = $this->createEventCashbook();
        $chitBody = new ChitBody(null, new ChronosDate(), null);
        $categoryId = 1;
        $category = new Cashbook\Category($categoryId, Operation::INCOME());
        $items = [
            new ChitItem(new Amount('100'), $category, ''),
            new ChitItem(new Amount('100'), $category, ''),
        ];
        $this->expectException(DuplicitCategory::class);
        $cashbook->addChit($chitBody, PaymentMethod::CASH(), $items, Helpers::mockCashbookCategories($categoryId));
    }

    public function testCreateChitWithVirtualCategory(): void
    {
        $cashbook = $this->createEventCashbook();
        $chitBody = new ChitBody(null, new ChronosDate(), null);

        $categories = [
            1 => m::mock(Category::class, ['getId' => 1, 'getOperationType' => Operation::EXPENSE(), 'isVirtual' => true]),
            2 => m::mock(Category::class, ['getId' => 2, 'getOperationType' => Operation::EXPENSE(), 'isVirtual' => false]),
        ];

        $category1 = Helpers::mockChitItemCategory(1);
        $category2 = Helpers::mockChitItemCategory(2);
        $items = [
            new ChitItem(new Amount('100'), $category1, ''),
            new ChitItem(new Amount('100'), $category2, ''),
        ];
        $this->expectException(SingleItemRestriction::class);
        $cashbook->addChit($chitBody, PaymentMethod::CASH(), $items, $categories);
    }

    private function assignFirstChitIdentity(Cashbook $cashbook): void
    {
        $reflection = new ReflectionClass($cashbook);
        $chits = $reflection->getProperty('chits')->getValue($cashbook);
        Helpers::assignIdentity($chits->first(), 1);
    }

    private function createEventCashbook(?CashbookId $cashbookId = null): Cashbook
    {
        return new Cashbook($cashbookId ?? CashbookId::generate(), CashbookType::get(CashbookType::EVENT));
    }
}
