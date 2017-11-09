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

    private const TABLE = 'pa_payment';

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

        $this->addGroupWithId1();
        $this->addPayments([$data]);

        $payment = $this->repository->find(1);

        $this->assertSame($data['groupId'], $payment->getGroupId());
        $this->assertSame($data['name'], $payment->getName());
        $this->assertSame($data['email'], $payment->getEmail());
        $this->assertSame($data['amount'], $payment->getAmount());
        $this->assertEquals(new \DateTimeImmutable($data['maturity']), $payment->getDueDate());
        $this->assertTrue($payment->getState()->equalsValue($data['state']), "Payment is not should be 'preparing'");
        $this->assertEquals(new VariableSymbol($data['vs']), $payment->getVariableSymbol(), 'Variable symbol doesn\'t match');
    }

    public function testGetMaxVariableSymbolForNoPaymentIsNull()
    {
        $this->assertNull($this->repository->getMaxVariableSymbol(10));
    }

    public function testGetMaxVariableSymbol()
    {
        $payments = array_fill(0, 5, [
            'groupId' => 1,
            'name' => 'Test',
            'email' => 'frantisekmasa1@gmail.com',
            'amount' => 200.0,
            'maturity' => '2017-10-29',
            'note' => '',
            'state' => Payment\State::PREPARING,
            'vs' => '100',
        ]);

        $payments[2]['vs'] = '100';
        $payments[3]['vs'] = '0100';
        $payments[4]['vs'] = '1000';

        $this->addGroupWithId1();
        $this->addPayments($payments);

        $this->assertEquals(new VariableSymbol('1000'), $this->repository->getMaxVariableSymbol(1));
    }

    private function addGroupWithId1()
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
    }

    private function addPayments(array $payments)
    {
        foreach($payments as $payment) {
            $this->tester->haveInDatabase(self::TABLE, $payment);
        }
    }

}
