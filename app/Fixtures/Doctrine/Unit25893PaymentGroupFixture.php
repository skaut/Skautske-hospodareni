<?php

declare(strict_types=1);

namespace App\Fixtures\Doctrine;

use App\Model\Common\EmailAddress;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\Group\PaymentDefaults;
use App\Model\Payment\Payment;
use App\Model\Payment\Services\IBankAccountAccessChecker;
use App\Model\Payment\Services\IOAuthAccessChecker;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Nette\DI\Container;
use Nettrine\Fixtures\ContainerAwareInterface;

use function assert;

/**
 * Creates sample payment groups and payments for unit 25893.
 *
 * Depends on Unit25893PaymentFixture which provides the BankAccount and GoogleOAuth.
 */
final class Unit25893PaymentGroupFixture extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    private const UNIT_ID = 25893;

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

        // Skip if groups already exist for this unit
        $existing = $manager->getRepository(Group::class)->findAll();
        foreach ($existing as $group) {
            assert($group instanceof Group);
            if (\in_array(self::UNIT_ID, $group->getUnitIds(), true)) {
                return; // idempotent
            }
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
            assert($payment instanceof Payment);
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
        );
        $manager->persist($groupClosed);
        $manager->flush();

        $this->addPayment($manager, $groupClosed, 'Anna Králová', 'anna.kralova@example.com', 3500.0, -30, '2025001');
        $this->addPayment($manager, $groupClosed, 'David Horák', 'david.horak@example.com', 3500.0, -30, '2025002');
        $this->addPayment($manager, $groupClosed, 'Lucie Marková', 'lucie.markova@example.com', 3500.0, -30, '2025003');

        $closedPayments = $manager->getRepository(Payment::class)->findBy(['groupId' => $groupClosed->getId()]);
        foreach ($closedPayments as $payment) {
            assert($payment instanceof Payment);
            $payment->completeManually(new DateTimeImmutable('-7 days'), 'Fixture Admin');
        }
        $groupClosed->close('Tábor proběhl, vše zaplaceno.');

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
        ];

        return new Group(
            [self::UNIT_ID],
            null, // no SkautisEntity binding
            $name,
            $paymentDefaults,
            new DateTimeImmutable(),
            $emails,
            null, // no OAuth for now (avoid access check complications)
            null, // no bank account binding in fixture
            $bankAccountChecker,
            $oauthChecker,
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
    ): void {
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
}
