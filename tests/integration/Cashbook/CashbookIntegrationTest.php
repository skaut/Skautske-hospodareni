<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Cake\Chronos\Date;
use Helpers;
use IntegrationTest;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitItem;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\Events\ChitWasAdded;
use Model\Cashbook\Events\ChitWasRemoved;
use Model\Cashbook\Events\ChitWasUpdated;

/**
 * These are in fact unit tests, that can't be tested without database right now, because chit ids are
 * autogenerated. This should be refactored to use composite keys ( @see \Model\Travel\Command ) and moved to unit suite
 */
class CashbookIntegrationTest extends IntegrationTest
{
    /**
     * @return string[]
     */
    public function getTestedEntites() : array
    {
        return [
            Cashbook::class,
            Chit::class,
            ChitItem::class,
        ];
    }

    public function testLockingLockedChitDoesNothing() : void
    {
        $cashbook = $this->createCashbookWithLockedChit();

        $cashbook->lockChit(1, 10);

        $this->assertEmpty($cashbook->extractEventsToDispatch());
    }

    public function testUnlockingUnlockedChitDoesNothing() : void
    {
        $cashbook = $this->createCashbookWithChit();

        $cashbook->unlockChit(1);

        $this->assertEmpty($cashbook->extractEventsToDispatch());
    }

    public function testUnlockedChitCanBeUpdated() : void
    {
        $cashbook      = $this->createCashbookWithLockedChit();
        $categoryId    = 1;
        $category      = Helpers::mockChitItemCategory($categoryId);
        $categories    = Helpers::mockCashbookCategories($categoryId);
        $amount        = new Cashbook\Amount('100');
        $date          = new Date('2017-11-17');
        $recipient     = new Recipient('František Maša');
        $paymentMethod = PaymentMethod::BANK();

        $cashbook->unlockChit(1);
        $cashbook->updateChit(
            1,
            new ChitBody(null, $date, $recipient),
            $paymentMethod,
            [new ChitItem($amount, $category, 'purpose')],
            $categories
        );

        $event = $cashbook->extractEventsToDispatch()[0];
        $this->assertInstanceOf(ChitWasUpdated::class, $event);
        /** @var ChitWasUpdated $event */
        $this->assertTrue($event->getCashbookId()->equals(CashbookId::fromString('10')));
    }

    public function testUpdateOfLockedChitThrowsException() : void
    {
        $cashbook      = $this->createCashbookWithLockedChit();
        $categoryId    = 666;
        $category      = Helpers::mockChitItemCategory($categoryId);
        $categories    = Helpers::mockCashbookCategories($categoryId);
        $date          = new Date('2017-11-17');
        $amount        = new Cashbook\Amount('100');
        $paymentMethod = PaymentMethod::CASH();
        $this->expectException(ChitLocked::class);

        $cashbook->updateChit(
            1,
            new ChitBody(null, $date, null),
            $paymentMethod,
            [new ChitItem($amount, $category, 'new-purpose')],
            $categories
        );
    }

    public function testUpdateOfNonExistentChitThrowsException() : void
    {
        $cashbook      = $this->createCashbookWithChit();
        $categoryId    = 666;
        $category      = Helpers::mockChitItemCategory($categoryId);
        $categories    = Helpers::mockCashbookCategories($categoryId);
        $amount        = new Cashbook\Amount('100');
        $date          = new Date('2017-11-17');
        $paymentMethod = PaymentMethod::CASH();

        $this->expectException(ChitNotFound::class);

        $cashbook->updateChit(
            2,
            new ChitBody(null, $date, null),
            $paymentMethod,
            [new ChitItem($amount, $category, 'new-purpose')],
            $categories
        );
    }

    public function testRemoveChit() : void
    {
        $cashbook = $this->createCashbookWithChit();

        $cashbook->removeChit(1);

        $this->assertSame([], $cashbook->getCategoryTotals()); // no chits => no categories

        $event = $cashbook->extractEventsToDispatch()[0];
        $this->assertInstanceOf(ChitWasRemoved::class, $event);
        /** @var ChitWasRemoved $event */
        $this->assertTrue($event->getCashbookId()->equals(CashbookId::fromString('10')));
        $this->assertSame('purpose', $event->getChitPurpose());
    }

    public function testRemovalOfLockedChitThrowsException() : void
    {
        $cashbook = $this->createCashbookWithLockedChit();

        $this->expectException(ChitLocked::class);

        $cashbook->removeChit(1);
    }

    public function testRemovalOfNonExistentChitThrowsException() : void
    {
        $cashbook = $this->createCashbookWithChit();

        $this->expectException(ChitNotFound::class);

        $cashbook->removeChit(2);
    }

    private function createCashbookWithLockedChit() : Cashbook
    {
        $cashbook = $this->createCashbookWithChit();
        $cashbook->lockChit(1, 10);
        $cashbook->extractEventsToDispatch();

        return $cashbook;
    }

    /**
     * @dataProvider getValidTransfers
     */
    public function testAddInverseTransferAddsChitToCalledCashbook(
        string $originalCashbookType,
        string $cashbookType,
        string $originalOperation,
        int $originalCategoryId,
        int $newCategoryId
    ) : void {
        $body             = new ChitBody(new ChitNumber('123'), new Date(), new Recipient('Maša'));
        $originalCashbook = new Cashbook(CashbookId::fromString('20'), CashbookType::get($originalCashbookType));
        $category         = Helpers::mockChitItemCategory($originalCategoryId, Operation::get($originalOperation));
        $categories       = Helpers::mockCashbookCategories($originalCategoryId);

        $originalCashbook->addChit(
            $body,
            PaymentMethod::CASH(),
            [new ChitItem(new Amount('101'), $category, 'transfer')],
            $categories
        );

        // to generate ID for chit
        $this->entityManager->persist($originalCashbook);
        $this->entityManager->flush();

        $cashbook = new Cashbook(CashbookId::fromString('10'), CashbookType::get($cashbookType));
        $cashbook->addInverseChit($originalCashbook, 1);
        $newChit = $cashbook->getChits()[0];

        // inverse chit must have inverse category type
        $this->assertSame(Operation::get($originalOperation)->getInverseOperation(), $newChit->getOperation());
        $this->assertTrue($body->withoutChitNumber()->equals($newChit->getBody()));
        $this->assertSame([$newCategoryId => 101.0], $cashbook->getCategoryTotals());
        $this->assertSame([$originalCategoryId => 101.0], $originalCashbook->getCategoryTotals()); // other cashbook is not changed by this action

        $events = $cashbook->extractEventsToDispatch();

        $this->assertCount(1, $events);

        /** @var ChitWasAdded $event */
        $event = $events[0];
        $this->assertTrue($event->getCashbookId()->equals(CashbookId::fromString('10')));
    }

    /**
     * @return string[][]
     */
    public function getValidTransfers() : array
    {
        return [ // expense -> income
            [
                CashbookType::TROOP,
                CashbookType::EVENT,
                Operation::EXPENSE,
                16, // "Převod do akce"
                13, // "Převod z oddílové pokladny"
            ],
            [ // income -> expense
                CashbookType::OFFICIAL_UNIT,
                CashbookType::TROOP,
                Operation::INCOME,
                13, // "Převod z oddílové pokladny"
                7, // "Převod do stř. pokladny"
            ],
        ];
    }

    /**
     * @dataProvider getInvalidInverseChitCategories
     */
    public function testAddInvalidInverseChitThrowsException(int $invalidCategoryId) : void
    {
        $originalCashbook = new Cashbook(CashbookId::fromString('20'), CashbookType::get(CashbookType::OFFICIAL_UNIT));
        $category         = Helpers::mockChitItemCategory($invalidCategoryId, Operation::EXPENSE());
        $categories       = Helpers::mockCashbookCategories($invalidCategoryId);
        $originalCashbook->addChit(
            new ChitBody(new ChitNumber('123'), new Date(), new Recipient('FM')),
            PaymentMethod::CASH(),
            [new ChitItem(new Amount('100'), $category, 'transfer')],
            $categories
        );

        // to generate ID for chit
        $this->entityManager->persist($originalCashbook);
        $this->entityManager->flush();

        $cashbook = new Cashbook(CashbookId::fromString('10'), CashbookType::get(CashbookType::EVENT));

        $this->expectException(InvalidCashbookTransfer::class);

        $cashbook->addInverseChit($originalCashbook, 1);
    }

    /**
     * @return string[]
     */
    public function getInvalidInverseChitCategories() : array
    {
        return [
            [13], // "Převod z odd. pokladny"
            [14], // "Převod do odd. pokladny"
        ];
    }

    private function createCashbookWithChit(string $type = CashbookType::EVENT) : Cashbook
    {
        $cashbook   = new Cashbook(CashbookId::fromString('10'), CashbookType::get($type));
        $categoryId = 666;
        $category   = Helpers::mockChitItemCategory($categoryId);

        $cashbook->addChit(
            new ChitBody(null, new Date(), null),
            PaymentMethod::CASH(),
            [new ChitItem(new Amount('100'), Helpers::mockChitItemCategory($categoryId), 'purpose')],
            Helpers::mockCashbookCategories($categoryId)
        );
        $cashbook->extractEventsToDispatch();

        // This assigns ID 1 to chit
        $this->entityManager->persist($cashbook);
        $this->entityManager->flush();

        return $cashbook;
    }

    public function testLock() : void
    {
        $cashbook   = new Cashbook(CashbookId::fromString('11'), CashbookType::get(CashbookType::EVENT));
        $chitBody   = new ChitBody(null, new Date(), null);
        $categoryId = 666;
        $category   = Helpers::mockChitItemCategory($categoryId);

        for ($i = 0; $i < 5; $i++) {
            $cashbook->addChit(
                $chitBody,
                PaymentMethod::CASH(),
                [new ChitItem(new Amount('100'), $category, 'purpose')],
                Helpers::mockCashbookCategories($categoryId)
            );
        }

        $this->entityManager->persist($cashbook);
        $this->entityManager->flush();

        $cashbook->lockChit(3, 1);

        $cashbook->lock(1);

        $chits = $cashbook->getChits();

        $this->assertCount(5, $chits);
        foreach ($chits as $chit) {
            $this->assertTrue($chit->isLocked());
        }
    }

    public function testCopyingChitRaisesEvent() : void
    {
        $sourceCashbook = $this->createCashbookWithChit();
        $targetCashbook = new Cashbook(CashbookId::fromString('1'), CashbookType::get(CashbookType::EVENT));

        $targetCashbook->copyChitsFrom([1], $sourceCashbook);

        $events = $targetCashbook->extractEventsToDispatch();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ChitWasAdded::class, $events[0]);
    }

    /**
     * @dataProvider getNonCampCashbookTypes
     */
    public function testCopyChitsBetweenTwoNonCampCashbooksWithSameType(string $cashbookType) : void
    {
        $sourceCashbook = $this->createCashbookWithChit($cashbookType);
        $targetCashbook = new Cashbook(CashbookId::fromString('2'), CashbookType::get($cashbookType));

        $targetCashbook->copyChitsFrom([1], $sourceCashbook);

        $this->assertSame(
            $sourceCashbook->getCategoryTotals(),
            $targetCashbook->getCategoryTotals()
        );
    }

    /**
     * @return string[][]
     */
    public function getNonCampCashbookTypes() : array
    {
        return [
            [CashbookType::EVENT],
            [CashbookType::TROOP],
            [CashbookType::OFFICIAL_UNIT],
        ];
    }

    /**
     * @dataProvider getDifferentCashbookTypes
     */
    public function testCopyChitsBetweenDifferentCashbooksWithDifferentCategories(string $sourceType, string $targetType) : void
    {
        $sourceCashbook = $this->createCashbookWithChit($sourceType);
        $targetCashbook = new Cashbook(CashbookId::fromString('2'), CashbookType::get($targetType));

        $targetCashbook->copyChitsFrom([1], $sourceCashbook);

        $chitAmount = $sourceCashbook->getChits()[0]->getAmount()->toFloat();
        $this->assertSame([Category::UNDEFINED_INCOME_ID => $chitAmount], $targetCashbook->getCategoryTotals());
    }

    /**
     * @return string[][]
     */
    public function getDifferentCashbookTypes() : array
    {
        return [
            [CashbookType::EVENT, CashbookType::TROOP],
            [CashbookType::CAMP, CashbookType::CAMP], // camps may have different categories
        ];
    }
}
