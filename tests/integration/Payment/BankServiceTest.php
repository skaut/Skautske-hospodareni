<?php

declare(strict_types=1);

namespace Tests\Integration\Pairing;

use Cake\Chronos\Date;
use DateTimeImmutable;
use Helpers;
use IntegrationTest;
use IntegrationTester;
use Mockery as m;
use Model\Bank\Fio\Transaction;
use Model\BankService;
use Model\DTO\Payment\PairingResult;
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
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;
use function date;
use function mt_rand;
use function reset;
use function sprintf;

class BankServiceTest extends IntegrationTest
{
    /** @var IntegrationTester */
    protected $tester;

    /** @var BankService */
    private $bankService;

    /** @var IPaymentRepository */
    private $payments;

    /** @var IGroupRepository */
    private $groups;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    protected function _before() : void
    {
        $this->tester->useConfigFiles(['Payment/BankServiceTest.neon']);
        parent::_before();
        $this->bankService  = $this->tester->grabService(BankService::class);
        $this->payments     = $this->tester->grabService(IPaymentRepository::class);
        $this->groups       = $this->tester->grabService(IGroupRepository::class);
        $this->bankAccounts = $this->tester->grabService(IBankAccountRepository::class);
    }

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots() : array
    {
        return [
            BankAccount::class,
            Group::class,
            Payment::class,
        ];
    }

    public function testPairGroups() : void
    {
        $I = $this->tester;

        $bankAccount = new BankAccount(
            1,
            'Hlavní',
            new BankAccount\AccountNumber(null, '2000942144', '2010'),
            'test-token',
            new DateTimeImmutable(),
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
                $this->createTransaction(500, ''),
            ]);

        $daysBack = 7;

        /** @var PairingResult[] $pairingResult */
        $pairingResults = $this->bankService->pairAllGroups([1], $daysBack);

        /** @var PairingResult $pairingResult */
        $pairingResult = reset($pairingResults);

        $dateSince = (new DateTimeImmutable(sprintf('- %d days', $daysBack)))->format('j.n.Y');
        $dateUntil = date('j.n.Y');
        $this->assertSame(2, $pairingResult->getCount());
        $this->assertSame($dateSince, $pairingResult->getSince()->format('j.n.Y'));
        $this->assertSame($dateUntil, $pairingResult->getUntil()->format('j.n.Y'));
        $this->assertSame(sprintf(
            'Platby na účtu "%s" byly spárovány (%d) za období %s - %s',
            $bankAccount->getName(),
            2,
            $dateSince,
            $dateUntil
        ), $pairingResult->getMessage());
    }

    private function addPayment(Group $group, float $amount, ?string $variableSymbol) : void
    {
        $payment = new Payment(
            $group,
            Random::generate(),
            null,
            $amount,
            new Date(),
            $variableSymbol === null ? null : new VariableSymbol($variableSymbol),
            null,
            null,
            ''
        );
        $this->payments->save($payment);
    }

    private function addGroup(?BankAccount $bankAccount) : Group
    {
        $paymentDefaults = new Group\PaymentDefaults(null, null, null, null);
        $emails          = Helpers::createEmails();

        $group = new Group(
            [1],
            null,
            'Test',
            $paymentDefaults,
            new DateTimeImmutable(),
            $emails,
            null,
            $bankAccount,
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );

        $this->groups->save($group);

        return $group;
    }

    private function createTransaction(float $amount, ?string $variableSymbol) : Transaction
    {
        return new Transaction(
            (string) mt_rand(1, 1000),
            new DateTimeImmutable(),
            $amount,
            '',
            '',
            (int) $variableSymbol,
            0,
            ''
        );
    }
}
