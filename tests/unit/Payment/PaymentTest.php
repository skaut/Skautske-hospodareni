<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Common\EmailAddress;
use App\Model\Common\Embeddable\Transaction;
use App\Model\Payment\DomainEvents\PaymentAmountWasChanged;
use App\Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use App\Model\Payment\DomainEvents\PaymentWasCreated;
use App\Model\Payment\Payment\State;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Helpers;
use InvalidArgumentException;
use Mockery as m;

use function assert;

class PaymentTest extends Unit
{
    private const AMOUNT = 500;

    public function testCreate(): void
    {
        $groupId = 29;
        $name = 'Jan novák';
        $email = new EmailAddress('test@gmail.com');
        $dueDate = new ChronosDate();
        $amount = 450;
        $variableSymbol = new VariableSymbol('454545');
        $constantSymbol = 666;
        $personId = 2;
        $note = 'Something';

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

    public function testReduceAmountBySplit(): void
    {
        $payment = $this->createPayment();
        $payment->extractEventsToDispatch();

        $payment->reduceAmountBySplit(150);

        $this->assertSame(350.0, $payment->getAmount());
        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PaymentAmountWasChanged::class, $events[0]);
    }

    public function testSplitCanReduceOriginalPaymentToZero(): void
    {
        $payment = $this->createPayment();

        $payment->reduceAmountBySplit(self::AMOUNT);

        $this->assertSame(0.0, $payment->getAmount());
    }

    public function testSplitCannotExceedOriginalAmount(): void
    {
        $payment = $this->createPayment();

        $this->expectException(InvalidArgumentException::class);
        $payment->reduceAmountBySplit(self::AMOUNT + 0.01);
    }

    public function testClosedPaymentCannotBeSplit(): void
    {
        $payment = $this->createPayment();
        $payment->completeManually(new DateTimeImmutable(), 'John Doe');

        $this->expectException(PaymentClosed::class);
        $payment->reduceAmountBySplit(100);
    }

    public function testPaymentCanReferenceSourcePayment(): void
    {
        $source = $this->createPayment();
        $split = new Payment(
            $this->mockGroup(29),
            'Jan novák',
            [],
            100,
            ChronosDate::now(),
            new VariableSymbol('123456'),
            null,
            null,
            '',
            $source,
        );

        $this->assertSame($source, $split->getSplitFromPayment());
        $this->assertSame($source->getId(), $split->getSplitFromPaymentId());
    }

    public function testCancel(): void
    {
        $time = new DateTimeImmutable();
        $payment = $this->createPayment();
        $payment->cancel($time);
        $this->assertSame(State::get(State::CANCELED), $payment->getState());
        $this->assertSame($time, $payment->getClosedAt());
    }

    public function testCancelingAlreadyCanceledPaymentThrowsException(): void
    {
        $time = new DateTimeImmutable();
        $payment = $this->createPayment();
        $payment->cancel($time);

        $this->expectException(PaymentClosed::class);
        $payment->cancel($time);
    }

    public function testCancelingCompletedPaymentUpdatesClosedAtAndState(): void
    {
        $time = new DateTimeImmutable();
        $payment = $this->createPayment();
        $payment->completeManually($time, 'John Doe');

        $canceledAt = $time->modify('+ 1 day');

        $payment->cancel($canceledAt);

        $this->assertSame(State::CANCELED, $payment->getState()->getValue());
        $this->assertSame($canceledAt, $payment->getClosedAt());
    }

    public function testCompletePayment(): void
    {
        $time = new DateTimeImmutable();
        $payment = $this->createPayment();
        $payment->completeManually($time, 'John Doe');
        $this->assertSame(State::get(State::COMPLETED), $payment->getState());
        $this->assertSame($time, $payment->getClosedAt());
    }

    public function testCompleteClosedPayment(): void
    {
        $time = new DateTimeImmutable();
        $payment = $this->createPayment();
        $payment->cancel($time);

        $this->expectException(PaymentClosed::class);
        $payment->completeManually($time, 'John Doe');
    }

    public function testCompletePaymentByUser(): void
    {
        $username = 'John Doe';
        $time = new DateTimeImmutable();
        $payment = $this->createPayment();
        $payment->completeManually($time, $username);
        $this->assertSame($username, $payment->getClosedByUsername());
        $this->assertTrue($payment->isClosed());
    }

    /** @dataProvider getVariableSymbolUpdates */
    public function testUpdateVariableSymbol(?VariableSymbol $old, VariableSymbol $new): void
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
        $symbol = '12345';
        $payment = $this->createPaymentWithVariableSymbol(new VariableSymbol($symbol));
        $payment->extractEventsToDispatch(); // Clear events collection

        $payment->updateVariableSymbol(new VariableSymbol($symbol));

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(0, $events);
    }

    public function testUpdateVariableForClosedPaymentThrowsException(): void
    {
        $payment = $this->createPayment();
        $payment->cancel(new DateTimeImmutable());

        $this->expectException(PaymentClosed::class);

        $payment->updateVariableSymbol(new VariableSymbol('789789'));
    }

    public function testUpdate(): void
    {
        $payment = $this->createPayment();
        $payment->extractEventsToDispatch(); // Clear events collection

        $name = 'František Maša';
        $amount = 300;
        $email = new EmailAddress('franta@gmail.com');
        $dueDate = ChronosDate::now();
        $variableSymbol = new VariableSymbol('789');
        $constantSymbol = 123;
        $note = 'Never pays!';

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

        $name = 'František Maša';
        $amount = 300;
        $email = new EmailAddress('franta@gmail.com');
        $dueDate = ChronosDate::now();
        $variableSymbol = new VariableSymbol('789');
        $constantSymbol = 123;
        $note = 'Never pays!';

        $payment->completeManually(new DateTimeImmutable(), 'John Doe');

        $this->expectException(PaymentClosed::class);

        $payment->update($name, [$email], $amount, $dueDate, $variableSymbol, $constantSymbol, $note);
    }

    public function testPairPaymentWithTransaction(): void
    {
        $payment = $this->createPayment();

        $transaction = new Transaction('21924318042', '123456789/0800', 'Joe Doe', 'abc', null);

        $payment->pairWithTransaction(new DateTimeImmutable(), $transaction);

        $this->assertSame($transaction, $payment->getTransaction());
        $this->assertTrue($payment->isClosed());
    }

    /** @dataProvider dataVariableSymbols */
    public function testChangingVariableSymbolViaUpdateRaisesEvent(?VariableSymbol $variableSymbol): void
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
    public function testUpdateWithSameVariableSymbolDoesNotRaiseEvent(?VariableSymbol $variableSymbol): void
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
        $type = EmailType::PAYMENT_COMPLETED;
        $time = new DateTimeImmutable();
        $sender = 'František Maša';

        $payment->recordSentEmail(EmailType::get($type), $time, $sender);

        $sentEmails = $payment->getSentEmails();
        $this->assertCount(1, $sentEmails);
        $this->assertSame($type, $sentEmails[0]->getType()->toString());
        $this->assertSame($time, $sentEmails[0]->getTime());
        $this->assertSame($sender, $sentEmails[0]->getSenderName());
    }

    public function testHasSentReminderToday(): void
    {
        $payment = $this->createPayment();
        $payment->recordSentEmail(
            EmailType::get(EmailType::PAYMENT_REMINDER),
            new DateTimeImmutable('2026-07-13 08:15:00'),
            'František Maša',
        );

        $this->assertTrue($payment->hasSentReminderToday(new DateTimeImmutable('2026-07-13 20:00:00')));
    }

    public function testHasSentReminderTodayIgnoresOtherEmailTypesAndDays(): void
    {
        $payment = $this->createPayment();
        $payment->recordSentEmail(
            EmailType::get(EmailType::PAYMENT_INFO),
            new DateTimeImmutable('2026-07-13 08:15:00'),
            'František Maša',
        );
        $payment->recordSentEmail(
            EmailType::get(EmailType::PAYMENT_REMINDER),
            new DateTimeImmutable('2026-07-12 08:15:00'),
            'František Maša',
        );

        $this->assertFalse($payment->hasSentReminderToday(new DateTimeImmutable('2026-07-13 20:00:00')));
    }

    public function testCanSendReminderOnlyAfterDueDate(): void
    {
        $today = new DateTimeImmutable('2026-07-13 12:00:00');

        $this->assertTrue($this->createPaymentWithDueDate(new ChronosDate('2026-07-12'))->canSendReminder($today));
        $this->assertFalse($this->createPaymentWithDueDate(new ChronosDate('2026-07-13'))->canSendReminder($today));
        $this->assertFalse($this->createPaymentWithDueDate(new ChronosDate('2026-07-14'))->canSendReminder($today));
    }

    public function testCanSendReminderRejectsClosedPayment(): void
    {
        $payment = $this->createPaymentWithDueDate(new ChronosDate('2026-07-12'));
        $payment->completeManually(new DateTimeImmutable('2026-07-12 18:00:00'), 'John Doe');

        $this->assertFalse($payment->canSendReminder(new DateTimeImmutable('2026-07-13 12:00:00')));
    }

    public function testCanSendReminderRejectsPaymentRemindedToday(): void
    {
        $payment = $this->createPaymentWithDueDate(new ChronosDate('2026-07-12'));
        $payment->recordSentEmail(
            EmailType::get(EmailType::PAYMENT_REMINDER),
            new DateTimeImmutable('2026-07-13 08:15:00'),
            'František Maša',
        );

        $this->assertFalse($payment->canSendReminder(new DateTimeImmutable('2026-07-13 20:00:00')));
        $this->assertTrue($payment->canSendReminder(new DateTimeImmutable('2026-07-14 08:00:00')));
    }

    public function testDuplicityEmailAddress(): void
    {
        $email1 = new EmailAddress('test@gmail.com');
        $email2 = new EmailAddress('test@gmail.com');
        $payment = new Payment($this->mockGroup(29), 'Jan novák', [$email1, $email2], self::AMOUNT, ChronosDate::now(), null, null, null, '');
        $this->assertCount(1, $payment->getEmailRecipients());
    }

    private function createPayment(): Payment
    {
        return $this->createPaymentWithVariableSymbol(new VariableSymbol('454545'));
    }

    private function createPaymentWithVariableSymbol(?VariableSymbol $symbol): Payment
    {
        return $this->createPaymentWithDueDateAndVariableSymbol(ChronosDate::now(), $symbol);
    }

    private function createPaymentWithDueDate(ChronosDate $dueDate): Payment
    {
        return $this->createPaymentWithDueDateAndVariableSymbol($dueDate, new VariableSymbol('454545'));
    }

    private function createPaymentWithDueDateAndVariableSymbol(ChronosDate $dueDate, ?VariableSymbol $symbol): Payment
    {
        $group = $this->mockGroup(29);

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
