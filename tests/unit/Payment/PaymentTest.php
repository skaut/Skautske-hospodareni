<?php

declare(strict_types=1);

namespace Model\Payment;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Helpers;
use InvalidArgumentException;
use Mockery as m;
use Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Payment\State;

class PaymentTest extends Unit
{
    public function testCreate() : void
    {
        $groupId        = 29;
        $name           = 'Jan novák';
        $email          = 'test@gmail.com';
        $dueDate        = new Date();
        $amount         = 450;
        $variableSymbol = new VariableSymbol('454545');
        $constantSymbol = 666;
        $personId       = 2;
        $note           = 'Something';

        $payment = new Payment(
            $this->mockGroup($groupId),
            $name,
            $email,
            $amount,
            $dueDate,
            $variableSymbol,
            $constantSymbol,
            $personId,
            $note
        );

        $this->assertSame($name, $payment->getName());
        $this->assertSame($email, $payment->getEmail());
        $this->assertSame((float) $amount, $payment->getAmount());
        $this->assertSame($dueDate, $payment->getDueDate());
        $this->assertSame($variableSymbol, $payment->getVariableSymbol());
        $this->assertSame($constantSymbol, $payment->getConstantSymbol());
        $this->assertSame($personId, $payment->getPersonId());
        $this->assertSame($note, $payment->getNote());
        $this->assertSame(State::get(State::PREPARING), $payment->getState());
        $this->assertSame($groupId, $payment->getGroupId());

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        /** @var PaymentWasCreated $event */
        $event = $events[0];
        $this->assertInstanceOf(PaymentWasCreated::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($variableSymbol, $event->getVariableSymbol());
    }

    public function testCantCreatePaymentWithNegativeAmount() : void
    {
        $this->expectException(InvalidArgumentException::class);

        new Payment(
            $this->mockGroup(10),
            'František Maša',
            'frantisekmasa1@gmail.com',
            -500,
            new Date(),
            null,
            null,
            null,
            ''
        );
    }

    public function testCantCreatePaymentWithZeroAmount() : void
    {
        $this->expectException(InvalidArgumentException::class);

        new Payment(
            $this->mockGroup(10),
            'František Maša',
            'frantisekmasa1@gmail.com',
            0,
            new Date(),
            null,
            null,
            null,
            ''
        );
    }

    public function testCancel() : void
    {
        $time    = Date::now();
        $payment = $this->createPayment();
        $payment->cancel($time);
        $this->assertSame(State::get(State::CANCELED), $payment->getState());
        $this->assertSame($time, $payment->getClosedAt());
    }

    public function testCancelingAlreadyCanceledPaymentThrowsException() : void
    {
        $time    = Date::now();
        $payment = $this->createPayment();
        $payment->cancel($time);

        $this->expectException(PaymentClosed::class);
        $payment->cancel($time);
    }

    public function testCancelingCompletedPaymentUpdatesClosedAtAndState() : void
    {
        $time    = Date::now();
        $payment = $this->createPayment();
        $payment->complete($time);

        $canceledAt = $time->modify('+ 30 minutes');

        $payment->cancel($canceledAt);

        $this->assertSame(State::CANCELED, $payment->getState()->getValue());
        $this->assertSame($canceledAt, $payment->getClosedAt());
    }

    public function testCompletePayment() : void
    {
        $time    = Date::now();
        $payment = $this->createPayment();
        $payment->complete($time);
        $this->assertSame(State::get(State::COMPLETED), $payment->getState());
        $this->assertSame($time, $payment->getClosedAt());
    }

    public function testCompleteClosedPayment() : void
    {
        $time    = Date::now();
        $payment = $this->createPayment();
        $payment->cancel($time);

        $this->expectException(PaymentClosed::class);
        $payment->complete($time);
    }

    /**
     * @dataProvider getVariableSymbolUpdates
     */
    public function testUpdateVariableSymbol(?VariableSymbol $old, VariableSymbol $new) : void
    {
        $payment = $this->createPaymentWithVariableSymbol($old);
        $payment->extractEventsToDispatch(); // Clear events collection;

        $payment->updateVariableSymbol($new);
        $this->assertSame($new, $payment->getVariableSymbol());

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        /** @var PaymentVariableSymbolWasChanged $event */
        $event = $events[0];
        $this->assertInstanceOf(PaymentVariableSymbolWasChanged::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($new, $event->getVariableSymbol());
    }

    /**
     * @return mixed[]
     */
    public function getVariableSymbolUpdates() : array
    {
        return [
            [new VariableSymbol('123'), new VariableSymbol('456')],
            [null, new VariableSymbol('456')],
        ];
    }

    public function testVariableSymbolUpdateToSameSymbolDoesntRaiseEvent() : void
    {
        $symbol  = '12345';
        $payment = $this->createPaymentWithVariableSymbol(new VariableSymbol($symbol));
        $payment->extractEventsToDispatch(); // Clear events collection

        $payment->updateVariableSymbol(new VariableSymbol($symbol));

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(0, $events);
    }

    public function testUpdateVariableForClosedPaymentThrowsException() : void
    {
        $payment = $this->createPayment();
        $payment->cancel(Date::now());

        $this->expectException(PaymentClosed::class);

        $payment->updateVariableSymbol(new VariableSymbol('789789'));
    }

    public function testUpdate() : void
    {
        $payment = $this->createPayment();
        $payment->extractEventsToDispatch(); // Clear events collection

        $name           = 'František Maša';
        $amount         = 300;
        $email          = 'franta@gmail.com';
        $dueDate        = Date::now();
        $variableSymbol = new VariableSymbol('789');
        $constantSymbol = 123;
        $note           = 'Never pays!';

        $payment->update($name, $email, $amount, $dueDate, $variableSymbol, $constantSymbol, $note);

        $this->assertSame($name, $payment->getName());
        $this->assertSame($email, $payment->getEmail());
        $this->assertSame((float) $amount, $payment->getAmount());
        $this->assertSame($dueDate, $payment->getDueDate());
        $this->assertSame($variableSymbol, $payment->getVariableSymbol());
        $this->assertSame($constantSymbol, $payment->getConstantSymbol());
        $this->assertSame($note, $payment->getNote());

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        /** @var PaymentVariableSymbolWasChanged $event */
        $event = $events[0];
        $this->assertInstanceOf(PaymentVariableSymbolWasChanged::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($variableSymbol, $event->getVariableSymbol());
    }

    public function testUpdateWithSameVariableSymbolDoesntThrowException() : void
    {
    }

    /**
     * @return VariableSymbol[][]
     */
    public function getVariableSymbolChanges() : array
    {
        return [
            [new VariableSymbol('123'), new VariableSymbol('')],
        ];
    }

    public function testCannotUpdateClosedPayment() : void
    {
        $payment = $this->createPayment();

        $name           = 'František Maša';
        $amount         = 300;
        $email          = 'franta@gmail.com';
        $dueDate        = Date::now();
        $variableSymbol = new VariableSymbol('789');
        $constantSymbol = 123;
        $note           = 'Never pays!';

        $payment->complete(Date::now());

        $this->expectException(PaymentClosed::class);

        $payment->update($name, $email, $amount, $dueDate, $variableSymbol, $constantSymbol, $note);
    }

    private function createPayment() : Payment
    {
        return $this->createPaymentWithVariableSymbol(new VariableSymbol('454545'));
    }

    private function createPaymentWithVariableSymbol(?VariableSymbol $symbol) : Payment
    {
        $group   = $this->mockGroup(29);
        $dueDate = Date::now();

        $payment = new Payment($group, 'Jan novák', 'test@gmail.com', 500, $dueDate, $symbol, 666, 454, 'Some note');
        Helpers::assignIdentity($payment, 1);

        return $payment;
    }

    private function mockGroup(int $id) : Group
    {
        $group = m::mock(Group::class);
        $group->shouldReceive('getId')->andReturn($id);

        return $group;
    }
}
