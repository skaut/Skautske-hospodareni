<?php

declare(strict_types=1);

namespace App\Fixtures\Doctrine;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Payment\IUnitResolver;
use App\Model\Payment\VariableSymbol;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Nette\DI\Container;
use Nettrine\Fixtures\ContainerAwareInterface;

use function assert;
use function sprintf;

final class InvoiceSequenceFixture extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    private const UNIT_ID = 25893;
    private const BANK_ACCOUNT_PREFIX = '19';
    private const BANK_ACCOUNT_NUMBER = '17608231';
    private const BANK_ACCOUNT_BANK_CODE = '0100';

    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager): void
    {
        $bankAccount = $this->findFixtureBankAccount($manager);

        // Řada 1: FA2026
        $seq1 = $this->ensureSequence($manager, $bankAccount, 'FA2026', 'Hlavní fakturační řada', 1);

        // Řada 2: ZF2026
        $seq2 = $this->ensureSequence($manager, $bankAccount, 'ZF2026', 'Zálohy a zálohové faktury', 2);

        $manager->flush();

        // Ukázkové faktury — vytvořit jen pokud řada nemá žádné faktury
        if ($seq1->getInvoices()->isEmpty()) {
            $this->createInvoice($manager, $seq1, $bankAccount, 1, 'FA202600001', '202600001', [
                ['price' => '5000.00', 'purpose' => 'Oddílové příspěvky', 'quantity' => 1, 'unit' => 'ks'],
            ], 'ACME s.r.o.');

            $this->createInvoice($manager, $seq1, $bankAccount, 2, 'FA202600002', '202600002', [
                ['price' => '3000.00', 'purpose' => 'Pronájem klubovny', 'quantity' => 1, 'unit' => 'měsíc'],
                ['price' => '1500.00', 'purpose' => 'Materiál na akci', 'quantity' => 1, 'unit' => 'ks'],
            ], 'Beta a.s.');

            $manager->flush();
        }

        if ($seq2->getInvoices()->isEmpty()) {
            $this->createInvoice($manager, $seq2, $bankAccount, 1, 'ZF202600001', '302600001', [
                ['price' => '10000.00', 'purpose' => 'Záloha na letní tábor', 'quantity' => 1, 'unit' => 'ks'],
            ], 'Gamma z.s.');

            $manager->flush();
        }
    }

    private function ensureSequence(
        ObjectManager $manager,
        ?BankAccount $bankAccount,
        string $prefix,
        string $description,
        int $sequenceId,
    ): InvoiceSequence {
        if (! $manager instanceof \Doctrine\ORM\EntityManagerInterface) {
            throw new \RuntimeException('Expected EntityManagerInterface');
        }

        $existing = $manager->createQueryBuilder()
            ->select('s')
            ->from(InvoiceSequence::class, 's')
            ->where('s.unit = :unit')
            ->andWhere('s.sequence = :sequence')
            ->andWhere('s.year = :year')
            ->setParameter('unit', self::UNIT_ID)
            ->setParameter('sequence', $prefix)
            ->setParameter('year', 2026)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existing instanceof InvoiceSequence) {
            return $existing;
        }

        $sequence = new InvoiceSequence(
            self::UNIT_ID,
            $prefix,
            2026,
            $description,
            $bankAccount,
            null,
            14,
        );
        $sequence->setSequenceId($sequenceId);

        $manager->persist($sequence);

        return $sequence;
    }

    /**
     * @param array<array{price: string, purpose: string, quantity: int, unit: string}> $items
     */
    private function createInvoice(
        ObjectManager $manager,
        InvoiceSequence $sequence,
        ?BankAccount $bankAccount,
        int $invoiceId,
        string $invoiceNumber,
        string $variableSymbol,
        array $items,
        string $customerName,
    ): void {
        $supplier = new InvoiceSupplier(
            self::UNIT_ID,
            '1. skautský oddíl Fixture',
            'Šikmá 15',
            'Praha',
            '11000',
            '12345678',
            '+420 111 222 333',
        );

        $customer = new InvoiceCustomer(
            $customerName,
            'Firemní 42',
            'Brno',
            '60200',
            '42',
            '',
            '87654321',
        );

        $invoice = new Invoice(
            $sequence,
            $supplier,
            $customer,
            'Fixture Loader',
            new DateTimeImmutable('+14 days'),
            new DateTimeImmutable('today'),
            new DateTimeImmutable('today'),
            InvoicePaymentType::TRANSFER,
            $bankAccount?->getNumber(),
            null,
            null,
            $bankAccount?->getNumber()?->getBankName(),
        );

        foreach ($items as $item) {
            $invoice->addItem(new InvoiceItem(
                BigDecimal::of($item['price']),
                $item['purpose'],
                $item['quantity'],
                $item['unit'],
            ));
        }

        $invoice->assignNumbering($invoiceId, $invoiceNumber, new VariableSymbol($variableSymbol));

        $manager->persist($invoice);
    }

    private function findFixtureBankAccount(ObjectManager $manager): ?BankAccount
    {
        $unitResolver = $this->container->getByType(IUnitResolver::class);
        assert($unitResolver instanceof IUnitResolver);

        $officialUnitId = $unitResolver->getOfficialUnitId(self::UNIT_ID);
        $expectedNumber = sprintf('%s-%s/%s', self::BANK_ACCOUNT_PREFIX, self::BANK_ACCOUNT_NUMBER, self::BANK_ACCOUNT_BANK_CODE);

        foreach ($manager->getRepository(BankAccount::class)->findBy(['unitId' => $officialUnitId]) as $account) {
            assert($account instanceof BankAccount);

            if ((string) $account->getNumber() === $expectedNumber) {
                return $account;
            }
        }

        return null;
    }

    /** @return list<class-string<AbstractFixture>> */
    public function getDependencies(): array
    {
        return [
            Unit25893PaymentFixture::class,
            InvoiceUnitSettingFixture::class,
        ];
    }
}
