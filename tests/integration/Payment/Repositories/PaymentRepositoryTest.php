<?php


namespace Model\Payment\Repositories;


use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\EventBus;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\PaymentNotFoundException;
use Model\Payment\VariableSymbol;

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

    public function testFind()
    {
        $this->tester->haveInDatabase('pa_group', [
            'id' => 1,
            'label' => 'test',
            'unitId' => 10,
            'state' => Group::STATE_OPEN,
            'state_info' => '',
            'email_template_subject' => '',
            'email_template_body' => '',
        ]);

        $data = [
            'groupId' => 1,
            'name' => 'Test',
            'email' => 'frantisekmasa1@gmail.com',
            'amount' => 200.0,
            'maturity' => '2017-10-29',
            'note' => '',
            'state' => Payment\State::PREPARING,
            'vs' => '100',
        ];

        $this->tester->haveInDatabase('pa_payment', $data);

        $payment = $this->repository->find(1);

        $this->assertSame($data['groupId'], $payment->getGroupId());
        $this->assertSame($data['name'], $payment->getName());
        $this->assertSame($data['email'], $payment->getEmail());
        $this->assertSame($data['amount'], $payment->getAmount());
        $this->assertEquals(new \DateTimeImmutable($data['maturity']), $payment->getDueDate());
        $this->assertTrue($payment->getState()->equalsValue($data['state']), "Payment is not should be 'preparing'");
        $this->assertEquals(new VariableSymbol($data['vs']), $payment->getVariableSymbol(), 'Variable symbol doesn\'t match');
    }

}
