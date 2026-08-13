<?php

declare(strict_types=1);

namespace Tests\Integration\Pairing;

use App\Model\Bank\BankService;
use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionPairing;
use App\Model\Bank\Transaction;
use App\Model\DTO\Payment\PairingResult;
use App\Model\Payment\FioClientStub;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IBankAccountRepository;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\VariableSymbol;
use BankingFixtures;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use IntegrationTest;
use IntegrationTester;
use Nette\Utils\Random;

use function assert;
use function date;
use function reset;
use function sprintf;

class BankServiceTest extends IntegrationTest
{
    use BankingFixtures;

    /**
     * @var IntegrationTester
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $tester;

    private BankService $bankService;

    private IPaymentRepository $payments;

    private IGroupRepository $groups;

    private IBankAccountRepository $bankAccounts;

    private int $nextTransactionId = 1;

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['Payment/BankServiceTest.neon']);

        parent::_before();

        $this->bankService = $this->tester->grabService(BankService::class);
        $this->payments = $this->tester->grabService(IPaymentRepository::class);
        $this->groups = $this->tester->grabService(IGroupRepository::class);
        $this->bankAccounts = $this->tester->grabService(IBankAccountRepository::class);
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            BankAccount::class,
            BankTransaction::class,
            BankTransactionPairing::class,
            Group::class,
            Payment::class,
        ];
    }

    public function testPairGroups(): void
    {
        $I = $this->tester;

        $bankAccount = $this->createBankAccountFixture('Hlavní');

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

        $pairingResults = $this->bankService->pairAllGroups([1], $daysBack);

        $pairingResult = reset($pairingResults);
        assert($pairingResult instanceof PairingResult);

        $dateSince = (new DateTimeImmutable(sprintf('- %d days', $daysBack)))->format('j.n.Y');
        $dateUntil = date('j.n.Y');
        $this->assertSame(1, $pairingResult->getCount());
        $this->assertSame($dateSince, $pairingResult->getSince()->format('j.n.Y'));
        $this->assertSame($dateUntil, $pairingResult->getUntil()->format('j.n.Y'));
        $this->assertSame(sprintf(
            'Platby na účtu "%s" byly spárovány (%d) za období %s - %s',
            $bankAccount->getName(),
            1,
            $dateSince,
            $dateUntil,
        ), $pairingResult->getMessage());
        self::assertSame([1], $this->activePairedPaymentIds());
    }

    public function testPairingOverMultipleGroupsUsesSameDomainCollisionRules(): void
    {
        $bankAccount = $this->createBankAccountFixture('Hlavní');
        $this->bankAccounts->save($bankAccount);

        $group1 = $this->addGroup($bankAccount);
        $group2 = $this->addGroup($bankAccount);

        $this->addPayment($group1, 200, '123');
        $this->addPayment($group1, 400, '345');
        $this->addPayment($group2, 400, '345');

        $this->tester->grabService(FioClientStub::class)
            ->setTransactions([
                $this->createTransaction(200, '123'),
                $this->createTransaction(400, '345'),
            ]);

        $pairingResults = $this->bankService->pairAllGroups([(int) $group1->getId(), (int) $group2->getId()], 7);

        self::assertCount(1, $pairingResults);
        self::assertSame([1], $this->activePairedPaymentIds());
    }

    private function addPayment(Group $group, float $amount, ?string $variableSymbol): void
    {
        $payment = new Payment(
            $group,
            Random::generate(),
            [],
            $amount,
            new ChronosDate(),
            $variableSymbol === null ? null : new VariableSymbol($variableSymbol),
            null,
            null,
            '',
        );
        $this->payments->save($payment);
    }

    private function addGroup(?BankAccount $bankAccount): Group
    {
        $group = $this->createPaymentGroupFixture($bankAccount);
        $this->groups->save($group);

        return $group;
    }

    private function createTransaction(float $amount, ?string $variableSymbol): Transaction
    {
        return $this->createFioTransactionFixture($this->nextTransactionId++, $amount, $variableSymbol, '');
    }

    /** @return int[] */
    private function activePairedPaymentIds(): array
    {
        $pairings = $this->entityManager->getRepository(BankTransactionPairing::class)->findBy(['cancelledAt' => null]);

        return array_map(
            static fn (BankTransactionPairing $pairing): int => (int) $pairing->getPayment()?->getId(),
            $pairings,
        );
    }
}
