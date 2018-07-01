<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Cake\Chronos\Date;
use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\EventBus;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;

class CashbookRepositoryTest extends \IntegrationTest
{
    private const TABLE      = 'ac_cashbook';
    private const CHIT_TABLE = 'ac_chits';

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

    public function getTestedEntites() : array
    {
        return [
            Cashbook::class,
            Cashbook\Chit::class,
        ];
    }

    public function testFindEmptyCashbook() : void
    {
        $this->tester->haveInDatabase(self::TABLE, ['id' => 10, 'type' => Cashbook\CashbookType::EVENT, 'note' => '']);

        $id = CashbookId::fromInt(10);

        $cashbook = $this->repository->find($id);

        $this->assertTrue($id->equals($cashbook->getId()));
        $this->assertSame(Cashbook\CashbookType::get(Cashbook\CashbookType::EVENT), $cashbook->getType());
    }

    public function testFindNotExistingCashbookThrowsException() : void
    {
        $this->expectException(CashbookNotFound::class);

        $this->repository->find(CashbookId::fromInt(1));
    }

    public function testSaveCashbookWithChits() : void
    {
        $chit = [
            'eventId' => 10,
            'date' => '1989-11-17',
            'num' => '123',
            'recipient' => 'František Maša',
            'price' => '100.00',
            'priceText' => '100',
            'purpose' => 'Purpose',
            'category' => 10,
            'category_operation_type' => Operation::INCOME,
            'payment_method' => Cashbook\PaymentMethod::BANK_TRANSFER,
        ];

        $cashbook = new Cashbook(CashbookId::fromInt(10), Cashbook\CashbookType::get(Cashbook\CashbookType::EVENT));
        $cashbook->updateChitNumberPrefix('test');
        $cashbook->updateNote('poznamka moje');

        $cashbook->addChit(
            new ChitBody(
                new Cashbook\ChitNumber($chit['num']),
                new Date($chit['date']),
                new Cashbook\Recipient($chit['recipient']),
                new Cashbook\Amount($chit['priceText']),
                $chit['purpose']
            ),
            $this->mockCategory($chit['category']),
            Cashbook\PaymentMethod::get($chit['payment_method'])
        );

        $this->repository->save($cashbook);

        $this->tester->seeInDatabase(self::TABLE, [
            'id' => 10,
            'type' => Cashbook\CashbookType::EVENT,
            'chit_number_prefix' => 'test',
            'note' => 'poznamka moje',
        ]);
        $this->tester->seeInDatabase(self::CHIT_TABLE, $chit);
    }

    private function mockCategory(int $id) : ICategory
    {
        return m::mock(ICategory::class, [
            'getId' => $id,
            'getOperationType' => Operation::get(Operation::INCOME),
        ]);
    }
}
