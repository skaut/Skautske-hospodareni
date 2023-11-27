<?php

declare(strict_types=1);

namespace Model\Payment;

use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Helpers;
use InvalidArgumentException;
use Mockery as m;
use Model\Common\EmailAddress;
use Model\Payment\DomainEvents\PaymentAmountWasChanged;
use Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Payment\State;
use Model\Payment\Payment\Transaction;

use function assert;

class PaymentTest extends Unit
{
    private const AMOUNT = 500;

    public function testCreate(): void
    {
        $groupId        = 29;
        $name           = 'Jan novák';
        $email          = new EmailAddress('test@gmail.com');
        $dueDate        = new ChronosDate();
        $amount         = 450;
        $variableSymbol = new VariableSymbol('454545');
        $constantSymbol = 666;
        $personId       = 2;
        $note           = 'Something';

        $payment = new Payment(
            $this->mockGroup($groupId),
            $name,
            [$email],
            $amount,
            $dueDate,
            $variableSymbol,
            $constantSymbol,
            $personId,
            $note,
        );

        $this->assertSame($name, $payment->getName());
        $this->assertSame($email, $payment->getEmailRecipients()[0]);
        $this->assertSame((float) $amount, $payment->getAmount());
        $this->assertSame($dueDate, $payment->getDueDate());
        $this->assertSame($variableSymbol, $payment->getVariableSymbol());
        $this->assertSame($constantSymbol, $payment->getConstantSymbol());
        $this->assertSame($personId, $payment->getPersonId());
        $this->assertSame($note, $payment->getNote());
        $this->assertSame(State::get(State::PREPARING), $payment->getState());
        $this->assertSame($groupId, $payment->getGroupId());
        $this->assertEmpty($payment->getSentEmails());

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        $event = $events[0];
        assert($event instanceof PaymentWasCreated);
        $this->assertInstanceOf(PaymentWasCreated::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($variableSymbol, $event->getVariableSymbol());
    }

    public function testCantCreatePaymentWithNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Payment(
            $this->mockGroup(10),
            'František Maša',
            [new EmailAddress('frantisekmasa1@gmail.com')],
            -500,
            new ChronosDate(),
            null,
            null,
            null,
            '',
        );
    }

    public function testCantCreatePaymentWithZeroAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Payment(
            $this->mockGroup(10),
            'František Maša',
            [new EmailAddress('frantisekmasa1@gmail.com')],
            0,
            new ChronosDate(),
            null,
            null,
            null,
            '',
        );
    }

    public function testCancel(): void
    {
        $time    = ChronosDate::now();
        $payment = $this->createPayment();
        $payment->cancel($time);
        $this->assertSame(State::get(State::CANCELED), $payment->getState());
        $this->assertSame($time, $payment->getClosedAt());
    }

    public function testCancelingAlreadyCanceledPaymentThrowsException(): void
    {
        $time    = ChronosDate::now();
        $payment = $this->createPayment();
        $payment->cancel($time);

        $this->expectException(PaymentClosed::class);
        $payment->cancel($time);
    }

    public function testCancelingCompletedPaymentUpdatesClosedAtAndState(): void
    {
        $time    = ChronosDate::now();
        $payment = $this->createPayment();
        $payment->completeManually($time, 'John Doe');

        $canceledAt = $time->modify('+ 30 minutes');

        $payment->cancel($canceledAt);

        $this->assertSame(State::CANCELED, $payment->getState()->getValue());
        $this->assertSame($canceledAt, $payment->getClosedAt());
    }

    public function testCompletePayment(): void
    {
        $time    = ChronosDate::now();
        $payment = $this->createPayment();
        $payment->completeManually($time, 'John Doe');
        $this->assertSame(State::get(State::COMPLETED), $payment->getState());
        $this->assertSame($time, $payment->getClosedAt());
    }

    public function testCompleteClosedPayment(): void
    {
        $time    = ChronosDate::now();
        $payment = $this->createPayment();
        $payment->cancel($time);

        $this->expectException(PaymentClosed::class);
        $payment->completeManually($time, 'John Doe');
    }

    public function testCompletePaymentByUser(): void
    {
        $username = 'John Doe';
        $time     = ChronosDate::now();
        $payment  = $this->createPayment();
        $payment->completeManually($time, $username);
        $this->assertSame($username, $payment->getClosedByUsername());
        $this->assertTrue($payment->isClosed());
    }

    /** @dataProvider getVariableSymbolUpdates */
    public function testUpdateVariableSymbol(VariableSymbol|null $old, VariableSymbol $new): void
    {
        $payment = $this->createPaymentWithVariableSymbol($old);
        $payment->extractEventsToDispatch(); // Clear events collection;

        $payment->updateVariableSymbol($new);
        $this->assertSame($new, $payment->getVariableSymbol());

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        $event = $events[0];
        assert($event instanceof PaymentVariableSymbolWasChanged);
        $this->assertInstanceOf(PaymentVariableSymbolWasChanged::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($new, $event->getVariableSymbol());
    }

    /** @return mixed[] */
    public function getVariableSymbolUpdates(): array
    {
        return [
            [new VariableSymbol('123'), new VariableSymbol('456')],
            [null, new VariableSymbol('456')],
        ];
    }

    public function testVariableSymbolUpdateToSameSymbolDoesntRaiseEvent(): void
    {
        $symbol  = '12345';
        $payment = $this->createPaymentWithVariableSymbol(new VariableSymbol($symbol));
        $payment->extractEventsToDispatch(); // Clear events collection

        $payment->updateVariableSymbol(new VariableSymbol($symbol));

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(0, $events);
    }

    public function testUpdateVariableForClosedPaymentThrowsException(): void
    {
        $payment = $this->createPayment();
        $payment->cancel(ChronosDate::now());

        $this->expectException(PaymentClosed::class);

        $payment->updateVariableSymbol(new VariableSymbol('789789'));
    }

    public function testUpdate(): void
    {
        $payment = $this->createPayment();
        $payment->extractEventsToDispatch(); // Clear events collection

        $name           = 'František Maša';
        $amount         = 300;
        $email          = new EmailAddress('franta@gmail.com');
        $dueDate        = ChronosDate::now();
        $variableSymbol = new VariableSymbol('789');
        $constantSymbol = 123;
        $note           = 'Never pays!';

        $payment->update($name, [$email], $amount, $dueDate, $variableSymbol, $constantSymbol, $note);

        $this->assertSame($name, $payment->getName());
        $this->assertSame($email, $payment->getEmailRecipients()[0]);
        $this->assertSame((float) $amount, $payment->getAmount());
        $this->assertSame($dueDate, $payment->getDueDate());
        $this->assertSame($variableSymbol, $payment->getVariableSymbol());
        $this->assertSame($constantSymbol, $payment->getConstantSymbol());
        $this->assertSame($note, $payment->getNote());

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(2, $events);

        $event = $events[0];
        assert($event instanceof PaymentVariableSymbolWasChanged);
        $this->assertInstanceOf(PaymentVariableSymbolWasChanged::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($variableSymbol, $event->getVariableSymbol());

        $event = $events[1];
        assert($event instanceof PaymentAmountWasChanged);
        $this->assertInstanceOf(PaymentAmountWasChanged::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($variableSymbol, $event->getVariableSymbol());
    }

    public function testUpdateWithSameVariableSymbolDoesntThrowException(): void
    {
    }

    /** @return VariableSymbol[][] */
    public function getVariableSymbolChanges(): array
    {
        return [
            [new VariableSymbol('123'), new VariableSymbol('')],
        ];
    }

    public function testCannotUpdateClosedPayment(): void
    {
        $payment = $this->createPayment();

        $name           = 'František Maša';
        $amount         = 300;
        $email          = new EmailAddress('franta@gmail.com');
        $dueDate        = ChronosDate::now();
        $variableSymbol = new VariableSymbol('789');
        $constantSymbol = 123;
        $note           = 'Never pays!';

        $payment->completeManually(ChronosDate::now(), 'John Doe');

        $this->expectException(PaymentClosed::class);

        $payment->update($name, [$email], $amount, $dueDate, $variableSymbol, $constantSymbol, $note);
    }

    public function testPairPaymentWithTransaction(): void
    {
        $payment = $this->createPayment();

        $transaction = new Transaction('21924318042', '123456789/0800', 'Joe Doe', 'abc', null);

        $payment->pairWithTransaction(ChronosDate::now(), $transaction);

        $this->assertSame($transaction, $payment->getTransaction());
        $this->assertTrue($payment->isClosed());
    }

    /** @dataProvider dataVariableSymbols */
    public function testChangingVariableSymbolViaUpdateRaisesEvent(VariableSymbol|null $variableSymbol): void
    {
        $payment = $this->createPaymentWithVariableSymbol($variableSymbol);
        $payment->extractEventsToDispatch(); // Clear events

        $newVariableSymbol = new VariableSymbol('12345');

        $payment->update('name', [], self::AMOUNT, ChronosDate::today(), $newVariableSymbol, null, '');

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        $event = $events[0];
        assert($event instanceof PaymentVariableSymbolWasChanged);
        $this->assertInstanceOf(PaymentVariableSymbolWasChanged::class, $event);
        $this->assertSame($payment->getGroupId(), $event->getGroupId());
        $this->assertSame($newVariableSymbol, $event->getVariableSymbol());
    }

    /** @dataProvider dataVariableSymbols */
    public function testUpdateWithSameVariableSymbolDoesNotRaiseEvent(VariableSymbol|null $variableSymbol): void
    {
        $payment = $this->createPaymentWithVariableSymbol($variableSymbol);
        $payment->extractEventsToDispatch(); // Clear events
        $payment->update('name', [], self::AMOUNT, ChronosDate::today(), $variableSymbol, null, '');

        $this->assertSame([], $payment->extractEventsToDispatch());
    }

    /** @return (VariableSymbol|null)[][] */
    public function dataVariableSymbols(): array
    {
        return [
            [new VariableSymbol('1234')],
            [null],
        ];
    }

    public function testUpdateWithDifferentAmountRaisesEvent(): void
    {
        $payment = $this->createPaymentWithVariableSymbol(null);
        $payment->extractEventsToDispatch(); // Clear events

        $payment->update('name', [], self::AMOUNT * 2, ChronosDate::today(), null, null, '');

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PaymentAmountWasChanged::class, $events[0]);

        $event = $events[0];
        assert($event instanceof PaymentAmountWasChanged);
        $this->assertSame($payment->getGroupId(), $event->getGroupId());
    }

    public function testUpdateWithSameAmountDoesNotRaiseEvent(): void
    {
        $payment = $this->createPaymentWithVariableSymbol(null);
        $payment->extractEventsToDispatch(); // Clear events
        $payment->update('name', [], self::AMOUNT, ChronosDate::today(), null, null, '');

        $this->assertSame([], $payment->extractEventsToDispatch());
    }

    public function testRecordSentEmail(): void
    {
        $payment = $this->createPayment();
        $type    = EmailType::PAYMENT_COMPLETED;
        $time    = new DateTimeImmutable();
        $sender  = 'František Maša';

        $payment->recordSentEmail(EmailType::get($type), $time, $sender);

        $sentEmails = $payment->getSentEmails();
        $this->assertCount(1, $sentEmails);
        $this->assertSame($type, $sentEmails[0]->getType()->toString());
        $this->assertSame($time, $sentEmails[0]->getTime());
        $this->assertSame($sender, $sentEmails[0]->getSenderName());
    }

    public function testDucliciteEmailAddress(): void
    {
        $email1  = new EmailAddress('test@gmail.com');
        $email2  = new EmailAddress('test@gmail.com');
        $payment = new Payment($this->mockGroup(29), 'Jan novák', [$email1, $email2], self::AMOUNT, ChronosDate::now(), null, null, null, '');
        $this->assertCount(1, $payment->getEmailRecipients());
    }

    private function createPayment(): Payment
    {
        return $this->createPaymentWithVariableSymbol(new VariableSymbol('454545'));
    }

    private function createPaymentWithVariableSymbol(VariableSymbol|null $symbol): Payment
    {
        $group   = $this->mockGroup(29);
        $dueDate = ChronosDate::now();

        $payment = new Payment($group, 'Jan novák', [new EmailAddress('test@gmail.com')], self::AMOUNT, $dueDate, $symbol, 666, 454, 'Some note');
        Helpers::assignIdentity($payment, 1);

        return $payment;
    }

    private function mockGroup(int $id): Group
    {
        $group = m::mock(Group::class);
        $group->shouldReceive('getId')->andReturn($id);

        return $group;
    }
}
