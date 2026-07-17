<?php

declare(strict_types=1);

namespace App\Fixtures\Doctrine;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction;
use App\Model\Bank\Entity\BankTransactionImportBatch;
use App\Model\Bank\Entity\BankTransactionPairing;
use App\Model\Bank\Enum\BankTransactionPairingMode;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Transaction;
use App\Model\Common\EmailAddress;
use App\Model\Common\Embeddable\Transaction as PaymentTransaction;
use App\Model\Common\UnitId;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\Group\PaymentDefaults;
use App\Model\Payment\IUnitResolver;
use App\Model\Payment\Payment;
use App\Model\Payment\Services\IBankAccountAccessChecker;
use App\Model\Payment\Services\IOAuthAccessChecker;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use Nette\DI\Container;
use Nettrine\Fixtures\ContainerAwareInterface;

use function sprintf;

/**
 * Creates sample payment groups and payments for unit 25893.
 *
 * Depends on Unit25893PaymentFixture which provides the BankAccount and GoogleOAuth.
 */
final class Unit25893PaymentGroupFixture extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    private const UNIT_ID = 25893;
    private const BANK_ACCOUNT_PREFIX = '19';
    private const BANK_ACCOUNT_NUMBER = '17608231';
    private const BANK_ACCOUNT_BANK_CODE = '0100';
    private const GOOGLE_EMAIL = 'fixtures-25893@hskauting.local';
    private const SCENARIO_GROUP_NAME = 'Fixture scénáře plateb a párování';

    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /** @return list<class-string<AbstractFixture>> */
    public function getDependencies(): array
    {
        return [Unit25893PaymentFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        $bankAccountChecker = $this->container->getByType(IBankAccountAccessChecker::class);
        $oauthChecker = $this->container->getByType(IOAuthAccessChecker::class);
        $bankAccount = $this->findFixtureBankAccount($manager);
        $googleOAuth = $this->findFixtureGoogleOAuth($manager);

        $scenarioGroup = $manager->getRepository(Group::class)->findOneBy(['name' => self::SCENARIO_GROUP_NAME]);
        if ($scenarioGroup instanceof Group) {
            $this->normalizeScenarioGroupPairing($scenarioGroup);
            $manager->flush();

            return;
        }

        // ── Group 1: Open, with payments in mixed states ──
        $groupOpen = $this->createGroup(
            $manager,
            'Členské příspěvky 2026',
            500.0,
            $this->nextWorkday(30),
            308,
            new VariableSymbol('2026001'),
            $bankAccountChecker,
            $oauthChecker,
            $bankAccount,
            $googleOAuth,
        );
        $manager->persist($groupOpen);
        $manager->flush(); // flush to get group ID for payments

        $this->addPayment($manager, $groupOpen, 'Jan Novák', 'jan.novak@example.com', 500.0, 30, '2026001');
        $this->addPayment($manager, $groupOpen, 'Petr Svoboda', 'petr.svoboda@example.com', 500.0, 30, '2026002');
        $this->addPayment($manager, $groupOpen, 'Marie Dvořáková', 'marie.dvorakova@example.com', 500.0, 30, '2026003');
        $this->addPayment($manager, $groupOpen, 'Tomáš Černý', 'tomas.cerny@example.com', 500.0, 30, '2026004');
        $this->addPayment($manager, $groupOpen, 'Kateřina Veselá', 'katerina.vesela@example.com', 500.0, 30, '2026005');

        // Complete two payments
        $payments = $manager->getRepository(Payment::class)->findBy(['groupId' => $groupOpen->getId()]);
        $completed = 0;
        foreach ($payments as $payment) {
            if (! $payment instanceof Payment) {
                throw new LogicException('Assertion failed.');
            }
            if ($completed < 2) {
                $payment->completeManually(new DateTimeImmutable('-3 days'), 'Fixture Admin');
                ++$completed;
            }
        }

        // ── Group 2: Open, registration group without payments yet ──
        $groupEmpty = $this->createGroup(
            $manager,
            'Registrace 2026',
            1200.0,
            $this->nextWorkday(60),
            null,
            new VariableSymbol('2026100'),
            $bankAccountChecker,
            $oauthChecker,
            $bankAccount,
            $googleOAuth,
        );
        $manager->persist($groupEmpty);

        // ── Group 3: Closed group with all payments completed ──
        $groupClosed = $this->createGroup(
            $manager,
            'Tábor Letní 2025',
            3500.0,
            $this->nextWorkday(-30), // past due date (weekday)
            308,
            new VariableSymbol('2025001'),
            $bankAccountChecker,
            $oauthChecker,
            $bankAccount,
            $googleOAuth,
        );
        $manager->persist($groupClosed);
        $manager->flush();

        $this->addPayment($manager, $groupClosed, 'Anna Králová', 'anna.kralova@example.com', 3500.0, -30, '2025001');
        $this->addPayment($manager, $groupClosed, 'David Horák', 'david.horak@example.com', 3500.0, -30, '2025002');
        $this->addPayment($manager, $groupClosed, 'Lucie Marková', 'lucie.markova@example.com', 3500.0, -30, '2025003');

        $closedPayments = $manager->getRepository(Payment::class)->findBy(['groupId' => $groupClosed->getId()]);
        foreach ($closedPayments as $payment) {
            if (! $payment instanceof Payment) {
                throw new LogicException('Assertion failed.');
            }
            $payment->completeManually(new DateTimeImmutable('-7 days'), 'Fixture Admin');
        }
        $groupClosed->close('Tábor proběhl, vše zaplaceno.');

        if ($bankAccount instanceof BankAccount) {
            $this->createScenarioGroup($manager, $bankAccount, $googleOAuth, $bankAccountChecker, $oauthChecker);
        }

        $manager->flush();
    }

    private function createGroup(
        ObjectManager $manager,
        string $name,
        float $amount,
        ChronosDate $dueDate,
        ?int $constantSymbol,
        ?VariableSymbol $nextVs,
        IBankAccountAccessChecker $bankAccountChecker,
        IOAuthAccessChecker $oauthChecker,
        ?BankAccount $bankAccount = null,
        ?GoogleOAuth $googleOAuth = null,
        bool $remindersEnabled = false,
    ): Group {
        $paymentDefaults = new PaymentDefaults($amount, $dueDate, $constantSymbol, $nextVs);

        $emails = [
            EmailType::PAYMENT_INFO => new EmailTemplate(
                'Informace o platbě – %groupname%',
                "Ahoj %name%,\n\nposíláme informace o platbě ve skupině %groupname%.\n\nČástka: %amount% Kč\nSplatnost: %maturity%\nVS: %vs%\nKS: %ks%\n\nDěkujeme,\n%user%",
            ),
            EmailType::PAYMENT_COMPLETED => new EmailTemplate(
                'Platba přijata – %groupname%',
                "Ahoj %name%,\n\npotvrzujeme přijetí platby ve skupině %groupname%.\n\nDěkujeme,\n%user%",
            ),
            EmailType::PAYMENT_REMINDER => new EmailTemplate(
                'Upomínka platby – %groupname%',
                "Ahoj %name%,\n\nevidujeme neuhrazenou platbu ve skupině %groupname%.\n\nČástka: %amount% Kč\nSplatnost: %maturity%\nVS: %vs%\n\nDěkujeme,\n%user%",
            ),
        ];

        return new Group(
            [self::UNIT_ID],
            null, // no SkautisEntity binding
            $name,
            $paymentDefaults,
            new DateTimeImmutable(),
            $emails,
            $googleOAuth?->getId(),
            $bankAccount,
            $bankAccountChecker,
            $oauthChecker,
            $remindersEnabled,
        );
    }

    private function addPayment(
        ObjectManager $manager,
        Group $group,
        string $name,
        string $email,
        float $amount,
        int $dueDaysFromNow,
        string $vs,
    ): Payment {
        $payment = new Payment(
            $group,
            $name,
            [new EmailAddress($email)],
            $amount,
            $this->nextWorkday($dueDaysFromNow),
            new VariableSymbol($vs),
            $group->getConstantSymbol(),
            null, // no personId
            '',
        );

        $manager->persist($payment);

        return $payment;
    }

    private function nextWorkday(int $daysFromNow): ChronosDate
    {
        $date = ChronosDate::today()->addDays($daysFromNow);

        // PaymentDefaults throws if dueDate is not a weekday
        while (! $date->isWeekday()) {
            $date = $date->addDays(1);
        }

        return $date;
    }

    private function createScenarioGroup(
        ObjectManager $manager,
        BankAccount $bankAccount,
        ?GoogleOAuth $googleOAuth,
        IBankAccountAccessChecker $bankAccountChecker,
        IOAuthAccessChecker $oauthChecker,
    ): void {
        $group = $this->createGroup(
            $manager,
            self::SCENARIO_GROUP_NAME,
            750.0,
            $this->nextWorkday(-10),
            308,
            new VariableSymbol('2026901'),
            $bankAccountChecker,
            $oauthChecker,
            $bankAccount,
            $googleOAuth,
            true,
        );
        $this->normalizeScenarioGroupPairing($group);
        $manager->persist($group);
        $manager->flush();

        $importedAt = new DateTimeImmutable('today 08:30:00');
        $batch = new BankTransactionImportBatch(
            $bankAccount,
            BankTransactionSource::GPC,
            'fixture-scenare-plateb.gpc',
            'fixture-scenare-plateb',
            $importedAt,
            'Fixture Loader',
            6,
        );
        $batch->markCompleted(6);
        $manager->persist($batch);

        $pairedPayment = $this->addPayment($manager, $group, 'Fixture zaplacená a spárovaná', 'sparovana@example.com', 750.0, -20, '2026901');
        $pairedTransaction = $this->addTransaction($manager, $bankAccount, $batch, 'paired', -2, 750.0, '2026901', 'Fixture spárovaná platba');
        $manager->flush();

        $pairedPayment->pairWithTransaction(new DateTimeImmutable('-2 days'), PaymentTransaction::fromBankTransaction($pairedTransaction));
        $manager->persist(BankTransactionPairing::forPayment(
            $pairedTransaction,
            $pairedTransaction->getTransactionKey(),
            $pairedPayment,
            BankTransactionPairingMode::MANUAL,
            new DateTimeImmutable('-2 days'),
            'Fixture Loader',
            $bankAccount->getId(),
            $bankAccount->getName(),
            (string) $bankAccount->getNumber(),
            self::BANK_ACCOUNT_BANK_CODE,
        ));

        $this->addPayment($manager, $group, 'Fixture po splatnosti bez upomínky', 'bez-upominky@example.com', 750.0, -14, '2026902');

        $remindedPayment = $this->addPayment($manager, $group, 'Fixture dnes upomenutá', 'dnes-upomenuta@example.com', 750.0, -12, '2026903');
        $remindedPayment->recordSentEmail(
            EmailType::get(EmailType::PAYMENT_REMINDER),
            new DateTimeImmutable('today 09:00:00'),
            'Fixture Loader',
        );

        $this->addPayment($manager, $group, 'Fixture budoucí splatnost', 'budouci@example.com', 750.0, 14, '2026904');

        $amountCandidate = $this->addPayment($manager, $group, 'Fixture ruční párování podle částky', 'castka@example.com', 640.0, -8, '2026905');
        if (! $amountCandidate instanceof Payment) {
            throw new LogicException('Assertion failed.');
        }
        $this->addTransaction($manager, $bankAccount, $batch, 'amount-only', -1, 640.0, null, 'Platba bez VS, shoda podle částky');

        $this->addPayment($manager, $group, 'Fixture automatická shoda VS a částka', 'automat@example.com', 880.0, -6, '2026906');
        $this->addTransaction($manager, $bankAccount, $batch, 'exact-match', -1, 880.0, '2026906', 'Shoda podle VS i částky');

        $this->addPayment($manager, $group, 'Fixture duplicitní shoda A', 'duplicita-a@example.com', 990.0, -6, '2026907');
        $this->addPayment($manager, $group, 'Fixture duplicitní shoda B', 'duplicita-b@example.com', 990.0, -6, '2026907');
        $this->addTransaction($manager, $bankAccount, $batch, 'duplicate-match', -1, 990.0, '2026907', 'Více plateb se stejným VS a částkou');

        $this->addTransaction($manager, $bankAccount, $batch, 'outgoing', -3, -250.0, null, 'Odchozí bankovní poplatek');
        $this->addTransaction($manager, $bankAccount, $batch, 'no-candidate', -4, 123.45, null, 'Bez VS a bez kandidáta');
    }

    private function normalizeScenarioGroupPairing(Group $group): void
    {
        $group->setAutomaticPairingEnabled(true);
        $group->setPairingDaysBack(7);
        $group->invalidateLastPairing();
        $group->disableEmail(EmailType::get(EmailType::PAYMENT_COMPLETED));
    }

    private function addTransaction(
        ObjectManager $manager,
        BankAccount $bankAccount,
        BankTransactionImportBatch $batch,
        string $key,
        int $daysFromNow,
        float $amount,
        ?string $variableSymbol,
        string $note,
    ): BankTransaction {
        $transaction = new BankTransaction(
            $bankAccount,
            new Transaction(
                'fixture-25893-'.$key,
                BankTransactionSource::GPC,
                (new DateTimeImmutable('today'))->modify(sprintf('%+d days', $daysFromNow)),
                $amount,
                '123456789/0800',
                'Fixture protistrana',
                $variableSymbol !== null ? (int) $variableSymbol : null,
                308,
                $note,
                'fixture-src-25893-'.$key,
            ),
            new DateTimeImmutable('today 08:30:00'),
            $batch,
        );

        $manager->persist($transaction);

        return $transaction;
    }

    private function findFixtureBankAccount(ObjectManager $manager): ?BankAccount
    {
        $unitResolver = $this->container->getByType(IUnitResolver::class);
        if (! $unitResolver instanceof IUnitResolver) {
            throw new LogicException('Assertion failed.');
        }
        $officialUnitId = $unitResolver->getOfficialUnitId(self::UNIT_ID);
        $expectedNumber = sprintf('%s-%s/%s', self::BANK_ACCOUNT_PREFIX, self::BANK_ACCOUNT_NUMBER, self::BANK_ACCOUNT_BANK_CODE);

        foreach ($manager->getRepository(BankAccount::class)->findBy(['unitId' => $officialUnitId]) as $account) {
            if (! $account instanceof BankAccount) {
                throw new LogicException('Assertion failed.');
            }
            if ((string) $account->getNumber() === $expectedNumber) {
                return $account;
            }
        }

        return null;
    }

    private function findFixtureGoogleOAuth(ObjectManager $manager): ?GoogleOAuth
    {
        $oauth = $manager->getRepository(GoogleOAuth::class)->findOneBy([
            'unitId' => new UnitId(self::UNIT_ID),
            'email' => self::GOOGLE_EMAIL,
        ]);

        return $oauth instanceof GoogleOAuth ? $oauth : null;
    }
}
