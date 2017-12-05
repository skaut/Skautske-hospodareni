<?php

namespace Model\Infrastructure\Repositories\Cashbook;

use Cake\Chronos\Date;
use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\EventBus;
use Model\Cashbook\Cashbook;
use Model\Cashbook\CashbookNotFoundException;

class CashbookRepositoryTest extends \IntegrationTest
{

    private const TABLE = 'ac_cashbook';
    private const CHIT_TABLE = 'ac_chits';

    /** @var CashbookRepository */
    private $repository;

    public function _before()
    {
        parent::_before();
        $this->repository = new CashbookRepository(
            $this->tester->grabService(EntityManager::class),
            $this->tester->grabService(EventBus::class)
        );
    }

    public function getTestedEntites(): array
    {
        return [
            Cashbook::class,
            Cashbook\Chit::class,
        ];
    }

    public function testFindEmptyCashbook(): void
    {
        $this->tester->haveInDatabase(self::TABLE, ['id' => 10, 'type' => Cashbook\CashbookType::EVENT]);

        $cashbook = $this->repository->find(10);

        $this->assertSame(10, $cashbook->getId());
        $this->assertSame(Cashbook\CashbookType::get(Cashbook\CashbookType::EVENT), $cashbook->getType());
    }

    public function testFindNotExistingCashbookThrowsException(): void
    {
        $this->expectException(CashbookNotFoundException::class);

        $this->repository->find(1);
    }

    public function testSaveCashbookWithChits(): void
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
        ];

        $cashbook = new Cashbook(10, Cashbook\CashbookType::get(Cashbook\CashbookType::EVENT));

        $cashbook->addChit(
            new Cashbook\ChitNumber($chit['num']),
            new Date($chit['date']),
            new Cashbook\Recipient($chit['recipient']),
            new Cashbook\Amount($chit['priceText']),
            $chit['purpose'],
            $chit['category']
        );

        $this->repository->save($cashbook);

        $this->tester->seeInDatabase(self::TABLE, [
            'id' => 10,
            'type' => Cashbook\CashbookType::EVENT,
        ]);
        $this->tester->seeInDatabase(self::CHIT_TABLE, $chit);
    }

}
