<?php

declare(strict_types=1);

namespace Tests\Integration\Pairing;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use App\Model\Payment\BankAccountService;
use App\Model\Payment\Group;
use App\Model\Payment\Repositories\IBankAccountRepository;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\UnitResolverStub;
use App\Model\Payment\VariableSymbol;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Helpers;
use IntegrationTest;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

class BankAccountServiceTest extends IntegrationTest
{
    private BankAccountService $bankAccountService;

    private IBankAccountRepository $bankAccounts;

    private IGroupRepository $groups;

    private InvoiceSequenceRepository $invoiceSequences;

    private InvoiceRepository $invoiceRepository;

    private EntityManagerInterface $em;

    private UnitResolverStub $unitResolver;

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['Payment/BankAccountServiceTest.neon']);

        parent::_before();

        $this->bankAccountService = $this->tester->grabService(BankAccountService::class);
        $this->bankAccounts = $this->tester->grabService(IBankAccountRepository::class);
        $this->groups = $this->tester->grabService(IGroupRepository::class);
        $this->invoiceSequences = $this->tester->grabService(InvoiceSequenceRepository::class);
        $this->invoiceRepository = $this->tester->grabService(InvoiceRepository::class);
        $this->em = $this->tester->grabService(EntityManagerInterface::class);
        $this->unitResolver = $this->tester->grabService(UnitResolverStub::class);
    }

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [
            BankAccount::class,
            Group::class,
            InvoiceSequence::class,
            Invoice::class,
            InvoiceItem::class,
        ];
    }

    public function testDisallowingBankAccountForSubunitsCascadesToGroups(): void
    {
        $this->unitResolver->setOfficialUnits([
            5 => 10,
            10 => 10,
        ]);
        $bankAccount = $this->createBankAccount();
        $bankAccount->allowForSubunits();
        $this->bankAccounts->save($bankAccount);

        $this->addGroup(5, $bankAccount);
        $this->addGroup(5, $bankAccount);
        $this->addGroup(10, $bankAccount); // This one belongs to official unit
        $sequence1 = $this->addSequence(5, $bankAccount, 1);
        $sequence2 = $this->addSequence(5, $bankAccount, 2);
        $sequence3 = $this->addSequence(10, $bankAccount, 3);

        $this->bankAccountService->disallowForSubunits($bankAccount->getId());

        $group1 = $this->groups->find(1); // subunit
        $group2 = $this->groups->find(2); // subunit
        $group3 = $this->groups->find(3);
        $sequence1 = $this->invoiceSequences->findOrFail($sequence1->getId());
        $sequence2 = $this->invoiceSequences->findOrFail($sequence2->getId());
        $sequence3 = $this->invoiceSequences->findOrFail($sequence3->getId());

        $this->assertNull($group1->getBankAccountId());
        $this->assertNull($group2->getBankAccountId());
        $this->assertSame($bankAccount->getId(), $group3->getBankAccountId());
        $this->assertNull($sequence1->getBankAccount());
        $this->assertNull($sequence2->getBankAccount());
        $this->assertSame($bankAccount->getId(), $sequence3->getBankAccount()?->getId());
    }

    public function testRemovingBankAccountDetachesLiveRelationsAndKeepsInvoiceSnapshot(): void
    {
        $this->unitResolver->setOfficialUnits([
            5 => 10,
            10 => 10,
        ]);
        $bankAccount = $this->createBankAccount();
        $bankAccount->allowForSubunits();
        $this->bankAccounts->save($bankAccount);

        $this->addGroup(5, $bankAccount);
        $sequence = $this->addSequence(5, $bankAccount, 1);
        $invoice = $this->addInvoice($sequence, $bankAccount, 'FA000001', '1');

        $this->bankAccountService->removeBankAccount($bankAccount->getId());
        $this->em->clear();

        $group = $this->groups->find(1);
        $reloadedSequence = $this->invoiceSequences->findOrFail($sequence->getId());
        $reloadedInvoice = $this->invoiceRepository->findOrFail($invoice->getId());

        $this->assertNull($group->getBankAccountId());
        $this->assertNull($reloadedSequence->getBankAccount());
        $this->assertNull($reloadedInvoice->getBankAccount());
        $this->assertSame((string) $bankAccount->getNumber(), (string) $reloadedInvoice->getAccountNumber());
        $this->assertSame($bankAccount->getName(), $reloadedInvoice->getBankName());
    }

    private function createBankAccount(): BankAccount
    {
        return new BankAccount(
            5, // official id is resolved to 10
            'Název',
            Helpers::createAccountNumber(),
            null,
            new DateTimeImmutable(),
            $this->unitResolver,
        );
    }

    private function addGroup(int $unitId, BankAccount $account): void
    {
        $paymentDefaults = new Group\PaymentDefaults(null, null, null, null);
        $emails = Helpers::createEmails();

        $group = new Group(
            [$unitId],
            null,
            'Nazev',
            $paymentDefaults,
            new DateTimeImmutable(),
            $emails,
            null,
            $account,
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );

        $this->groups->save($group);
    }

    private function addSequence(int $unitId, BankAccount $account, int $id): InvoiceSequence
    {
        $sequence = new InvoiceSequence($unitId, 'FA', 2026, 'Faktury', $account, null, 14, '00001');
        $sequence->setSequenceId($id);
        Helpers::assignIdentity($sequence, $id);
        $this->invoiceSequences->save($sequence);

        return $sequence;
    }

    private function addInvoice(InvoiceSequence $sequence, BankAccount $account, string $number, string $variableSymbol): Invoice
    {
        $invoice = new Invoice(
            $sequence,
            new InvoiceSupplier(10, 'Dodavatel', 'Ulice', 'Praha', '11000', '12345678'),
            new InvoiceCustomer('Odběratel', 'Ulice', 'Brno', '60200', '1', '', ''),
            'Tester',
            new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-03-10'),
            new DateTimeImmutable('2026-03-10'),
            InvoicePaymentType::TRANSFER,
            $account->getNumber(),
            null,
            null,
            $account->getName(),
        );
        $invoice->addItem(new InvoiceItem(BigDecimal::of('150.00'), 'Položka'));
        $invoice->assignNumbering(1, $number, new VariableSymbol($variableSymbol));
        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }
}
