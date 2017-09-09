<?php


namespace Model\Payment\Repositories;


use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\EventBus;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\PaymentNotFoundException;

class PaymentRepositoryTest extends \IntegrationTest
{

    /** @var EntityManager */
    private $entityManager;

    /** @var PaymentRepository */
    private $repository;

    public function getTestedEntites(): array
    {
        return [
            Payment::class,
            Group::class,
        ];
    }

    protected function _before()
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);
        parent::_before();
        $this->entityManager = $this->tester->grabService(EntityManager::class);
        $this->repository = new PaymentRepository($this->entityManager, new EventBus());
    }

    public function testFindNotSavedPaymentThrowsException()
    {
        $this->expectException(PaymentNotFoundException::class);

        $this->repository->find(10);
    }

}
