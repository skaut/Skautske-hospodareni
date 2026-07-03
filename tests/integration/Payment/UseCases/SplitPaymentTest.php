<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Common\EmailAddress;
use App\Model\Infrastructure\Repositories\Payment\GroupRepository;
use App\Model\Infrastructure\Repositories\Payment\PaymentRepository;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Payment\Commands\Payment\SplitPayment;
use App\Model\Payment\Commands\Payment\SplitPaymentPart;
use App\Model\Payment\Handlers\Payment\SplitPaymentHandler;
use App\Model\Payment\Services\VariableSymbolCollisionChecker;
use DateTimeImmutable;
use Helpers;
use Hskauting\Tests\NullEventBus;
use IntegrationTest;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

final class SplitPaymentTest extends IntegrationTest
{
    private PaymentRepository $payments;

    private SplitPaymentHandler $handler;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            Group::class,
            Payment::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $eventBus = new NullEventBus();
        $this->payments = new PaymentRepository($this->entityManager, $eventBus);
        $groups = new GroupRepository($this->entityManager, $eventBus);
        $this->handler = new SplitPaymentHandler(
            $this->payments,
            $groups,
            new VariableSymbolCollisionChecker($this->payments, new InvoiceRepository($this->entityManager)),
            $this->entityManager,
        );

        $groups->save(new Group(
            [1],
            null,
            'Skupina',
            Helpers::createEmptyPaymentDefaults(),
            new DateTimeImmutable(),
            [EmailType::PAYMENT_INFO => new EmailTemplate('', '')],
            null,
            null,
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        ));
    }

    public function testSplitCreatesMultiplePaymentsAndReducesSource(): void
    {
        $source = new Payment(
            $this->group(),
            'Účastnický poplatek',
            [new EmailAddress('participant@example.com')],
            1000,
            Helpers::getValidDueDate(),
            new VariableSymbol('100'),
            308,
            123,
            'Poznámka',
        );
        $this->payments->save($source);

        ($this->handler)(new SplitPayment($source->getId(), [
            new SplitPaymentPart(new VariableSymbol('101'), 300, 'Faktura zaměstnavatele'),
            new SplitPaymentPart(new VariableSymbol('102'), 200),
        ]));

        $payments = $this->payments->findByGroup(1);
        $this->assertCount(3, $payments);
        $this->assertSame(500.0, $payments[0]->getAmount());

        foreach ([$payments[1], $payments[2]] as $splitPayment) {
            $this->assertSame($source->getId(), $splitPayment->getSplitFromPaymentId());
            $this->assertSame($source->getName(), $splitPayment->getName());
            $this->assertEquals($source->getEmailRecipients(), $splitPayment->getEmailRecipients());
            $this->assertSame($source->getDueDate(), $splitPayment->getDueDate());
            $this->assertSame($source->getConstantSymbol(), $splitPayment->getConstantSymbol());
            $this->assertSame($source->getPersonId(), $splitPayment->getPersonId());
        }

        $this->assertSame(300.0, $payments[1]->getAmount());
        $this->assertSame('101', (string) $payments[1]->getVariableSymbol());
        $this->assertSame('Faktura zaměstnavatele', $payments[1]->getNote());
        $this->assertSame(200.0, $payments[2]->getAmount());
        $this->assertSame('102', (string) $payments[2]->getVariableSymbol());
        $this->assertSame($source->getNote(), $payments[2]->getNote());
    }

    public function testSplitRejectsAmountAboveSourceAmount(): void
    {
        $source = $this->createSourcePayment();

        $this->expectException(InvalidPaymentSplit::class);
        ($this->handler)(new SplitPayment($source->getId(), [
            new SplitPaymentPart(new VariableSymbol('101'), 1000.01),
        ]));
    }

    public function testSplitRejectsDuplicateVariableSymbols(): void
    {
        $source = $this->createSourcePayment();

        $this->expectException(InvalidPaymentSplit::class);
        ($this->handler)(new SplitPayment($source->getId(), [
            new SplitPaymentPart(new VariableSymbol('101'), 100),
            new SplitPaymentPart(new VariableSymbol('101'), 100),
        ]));
    }

    public function testSplitRejectsSourceVariableSymbol(): void
    {
        $source = $this->createSourcePayment();

        $this->expectException(InvalidPaymentSplit::class);
        ($this->handler)(new SplitPayment($source->getId(), [
            new SplitPaymentPart(new VariableSymbol('100'), 100),
        ]));
    }

    public function testSplitRejectsVariableSymbolAlreadyUsedInGroup(): void
    {
        $source = $this->createSourcePayment();
        $this->payments->save(new Payment(
            $this->group(),
            'Jiná platba',
            [],
            100,
            Helpers::getValidDueDate(),
            new VariableSymbol('101'),
            null,
            null,
            '',
        ));

        $this->expectException(InvalidPaymentSplit::class);
        $this->expectExceptionMessage('Variabilní symbol 101 je už použitý v této platební skupině.');
        ($this->handler)(new SplitPayment($source->getId(), [
            new SplitPaymentPart(new VariableSymbol('101'), 100),
        ]));
    }

    private function createSourcePayment(): Payment
    {
        $payment = new Payment(
            $this->group(),
            'Platba',
            [],
            1000,
            Helpers::getValidDueDate(),
            new VariableSymbol('100'),
            null,
            null,
            '',
        );
        $this->payments->save($payment);

        return $payment;
    }

    private function group(): Group
    {
        $group = $this->entityManager->find(Group::class, 1);
        $this->assertInstanceOf(Group::class, $group);

        return $group;
    }
}
