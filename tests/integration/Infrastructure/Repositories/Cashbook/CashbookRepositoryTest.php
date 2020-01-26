<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Cake\Chronos\Date;
use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\EventBus;
use Helpers;
use IntegrationTest;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\Operation;

class CashbookRepositoryTest extends IntegrationTest
{
    private const TABLE           = 'ac_cashbook';
    private const CHIT_TABLE      = 'ac_chits';
    private const CHIT_ITEM_TABLE = 'ac_chits_item';

    /** @var CashbookRepository */
    private $repository;

    public function _before() : void
    {
        parent::_before();
        $this->repository = new CashbookRepository(
            $this->tester->grabService(EntityManager::class),
            $this->tester->grabService(EventBus::class)
        );
    }

    /**
     * @return string[]
     */
    public function getTestedAggregateRoots() : array
    {
        return [Cashbook::class];
    }

    public function testFindEmptyCashbook() : void
    {
        $this->tester->haveInDatabase(self::TABLE, ['id' => 10, 'type' => Cashbook\CashbookType::EVENT, 'note' => '']);

        $id = CashbookId::fromString('10');

        $cashbook = $this->repository->find($id);

        $this->assertTrue($id->equals($cashbook->getId()));
        $this->assertSame(Cashbook\CashbookType::get(Cashbook\CashbookType::EVENT), $cashbook->getType());
    }

    public function testFindNotExistingCashbookThrowsException() : void
    {
        $this->expectException(CashbookNotFound::class);

        $this->repository->find(CashbookId::fromString('1'));
    }

    public function testSaveCashbookWithChits() : void
    {
        $chit = [
            'eventId' => 10,
            'date' => '1989-11-17',
            'num' => '123',
            'recipient' => 'František Maša',
            'payment_method' => Cashbook\PaymentMethod::BANK,
        ];

        $chitItem = [
            'purpose' => 'Purpose',
            'price' => '100.00',
            'priceText' => '100',
            'category' => 10,
            'category_operation_type' => Operation::INCOME,
        ];

        $cashbook = new Cashbook(CashbookId::fromString('10'), Cashbook\CashbookType::get(Cashbook\CashbookType::EVENT));
        $cashbook->updateChitNumberPrefix('test');
        $cashbook->updateNote('poznamka moje');
        $category = Helpers::mockChitItemCategory($chitItem['category']);

        $cashbook->addChit(
            new ChitBody(
                new Cashbook\ChitNumber($chit['num']),
                new Date($chit['date']),
                new Cashbook\Recipient($chit['recipient'])
            ),
            Cashbook\PaymentMethod::get($chit['payment_method']),
            [new Cashbook\ChitItem(new Cashbook\Amount($chitItem['priceText']), $category, $chitItem['purpose'])],
            Helpers::mockCashbookCategories($chitItem['category'])
        );

        $this->repository->save($cashbook);

        $this->tester->seeInDatabase(self::TABLE, [
            'id' => 10,
            'type' => Cashbook\CashbookType::EVENT,
            'chit_number_prefix' => 'test',
            'note' => 'poznamka moje',
        ]);
        $this->tester->seeInDatabase(self::CHIT_TABLE, $chit);
        $this->tester->seeInDatabase(self::CHIT_ITEM_TABLE, $chitItem);
    }

    /**
     * @see https://github.com/skaut/Skautske-hospodareni/issues/914
     */
    public function testOrphanedChitItemsAreRemoved() : void
    {
        $cashbook      = new Cashbook(CashbookId::generate(), Cashbook\CashbookType::get(Cashbook\CashbookType::CAMP));
        $body          = new ChitBody(null, Date::today(), null);
        $paymentMethod = Cashbook\PaymentMethod::BANK();
        $category      = Helpers::mockChitItemCategory(1);
        $categoryList  = Helpers::mockCashbookCategories(1, Operation::INCOME());

        $cashbook->addChit(
            $body,
            $paymentMethod,
            [new Cashbook\ChitItem(Cashbook\Amount::fromFloat(100), $category, 'foo')],
            $categoryList
        );

        $this->repository->save($cashbook);

        $cashbook->updateChit(
            1,
            $body,
            $paymentMethod,
            [new Cashbook\ChitItem(Cashbook\Amount::fromFloat(10), $category, 'foo')],
            $categoryList
        );

        $this->repository->save($cashbook);

        $this->assertSame(1, $this->entityManager->getRepository(Cashbook\ChitItem::class)->count([]));
    }
}
