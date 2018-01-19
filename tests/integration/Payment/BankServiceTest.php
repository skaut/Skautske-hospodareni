<?php

namespace Tests\Integration\Pairing;


use Mockery as m;
use Model\Bank\Fio\Transaction;
use Model\BankService;
use Model\Payment\BankAccount;
use Model\Payment\FioClientStub;
use Model\Payment\Group;
use Model\Payment\IUnitResolver;
use Model\Payment\Payment;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Payment\VariableSymbol;
use Nette\Utils\Random;

class BankServiceTest extends \IntegrationTest
{

    /** @var \IntegrationTester */
    protected $tester;

    /** @var BankService */
    private $bankService;

    /** @var IPaymentRepository */
    private $payments;

    /** @var IGroupRepository */
    private $groups;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    protected function _before()
    {
        $this->tester->useConfigFiles(['Payment/BankServiceTest.neon']);
        parent::_before();
        $this->bankService = $this->tester->grabService(BankService::class);
        $this->payments = $this->tester->grabService(IPaymentRepository::class);
        $this->groups = $this->tester->grabService(IGroupRepository::class);
        $this->bankAccounts = $this->tester->grabService(IBankAccountRepository::class);
    }

    protected function getTestedEntites(): array
    {
        return [
            BankAccount::class,
            Group::class,
            Group\Email::class,
            Payment::class,
        ];
    }


    public function testPairGroups()
    {
        $I = $this->tester;

        $bankAccount = new BankAccount(
            1,
            'Hlavní',
            new BankAccount\AccountNumber(NULL, '2000942144', '2010'),
            'test-token',
            new \DateTimeImmutable(),
            m::mock(IUnitResolver::class, ['getOfficialUnitId' => 1])
        );

        $this->bankAccounts->save($bankAccount);

        $group1 = $this->addGroup($bankAccount); // ID: 1
        $group2 = $this->addGroup($bankAccount); // ID: 2

        $this->addPayment($group1, 200, '123');
        $this->addPayment($group1, 400, '345');
        $this->addPayment($group2, 400, '345');


        $I->grabService(FioClientStub::class)
            ->setTransactions([
                $this->createTransaction(200, '123'),
                $this->createTransaction(400, '345'),
                $this->createTransaction(500, '')
            ]);

        $this->assertSame(
            2,
            $this->bankService->pairAllGroups([1])
        );
    }


    private function addPayment(Group $group, float $amount, ?string $variableSymbol)
    {
        $payment = new Payment(
            $group,
            Random::generate(),
            NULL,
            $amount,
            new \DateTimeImmutable(),
            $variableSymbol === NULL ? NULL : new VariableSymbol($variableSymbol),
            NULL,
            NULL,
            ''
        );
        $this->payments->save($payment);
    }


    private function addGroup(?BankAccount $bankAccount): Group
    {
        $group = new Group(
            1,
            NULL,
            'Testovací skupina',
            new Group\PaymentDefaults(NULL, NULL, NULL, NULL),
            new \DateTimeImmutable(),
            new \Model\Payment\EmailTemplate('', ''),
            NULL,
            $bankAccount
        );

        $this->groups->save($group);

        return $group;
    }


    private function createTransaction(float $amount, ?string $variableSymbol): Transaction
    {
        return new Transaction(
            mt_rand(1, 1000),
            new \DateTimeImmutable(),
            $amount,
            '',
            '',
            (int)$variableSymbol,
            0,
            ''
        );
    }

}
