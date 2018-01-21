<?php

namespace Model\Payment;

use DateTimeImmutable;
use Mockery as m;
use Model\Payment\DomainEvents\PaymentVariableSymbolWasChanged;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Payment\State;

class PaymentTest extends \Codeception\Test\Unit
{


    public function testCreate(): void
    {
        $groupId = 29;
        $name = "Jan novák";
        $email = "test@gmail.com";
        $dueDate = new DateTimeImmutable();
        $amount = 450;
        $variableSymbol = new VariableSymbol('454545');
        $constantSymbol = 666;
        $personId = 2;
        $note = 'Something';

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
        $this->assertSame((float)$amount, $payment->getAmount());
        $this->assertSame($dueDate, $payment->getDueDate());
        $this->assertSame($variableSymbol, $payment->getVariableSymbol());
        $this->assertSame($constantSymbol, $payment->getConstantSymbol());
        $this->assertSame($personId, $payment->getPersonId());
        $this->assertSame($note, $payment->getNote());
        $this->assertSame(State::get(State::PREPARING), $payment->getState());
        $this->assertSame($groupId, $payment->getGroupId());

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        /* @var $event PaymentWasCreated */
        $event = $events[0];
        $this->assertInstanceOf(PaymentWasCreated::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($variableSymbol, $event->getVariableSymbol());
    }

    public function testCantCreatePaymentWithNegativeAmount()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Payment(
            $this->mockGroup(10),
            'František Maša',
            'frantisekmasa1@gmail.com',
            -500,
            new DateTimeImmutable(),
            NULL,
            NULL,
            NULL,
            ''
        );
    }

    public function testCantCreatePaymentWithZeroAmount()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Payment(
            $this->mockGroup(10),
            'František Maša',
            'frantisekmasa1@gmail.com',
            0,
            new DateTimeImmutable(),
            NULL,
            NULL,
            NULL,
            ''
        );
    }

    public function testCancel()
    {
        $time = new DateTimeImmutable();
        $payment = $this->createPayment();
        $payment->cancel($time);
        $this->assertSame(State::get(State::CANCELED), $payment->getState());
        $this->assertSame($time, $payment->getClosedAt());
    }

    public function testCancelClosedPayment()
    {
        $time = new DateTimeImmutable();
        $payment = $this->createPayment();
        $payment->cancel($time);

        $this->expectException(PaymentClosedException::class);
        $payment->cancel($time);
    }

    public function testCompletePayment()
    {
        $time = new DateTimeImmutable();
        $payment = $this->createPayment();
        $payment->complete($time);
        $this->assertSame(State::get(State::COMPLETED), $payment->getState());
        $this->assertSame($time, $payment->getClosedAt());
    }

    public function testCompleteClosedPayment()
    {
        $time = new DateTimeImmutable();
        $payment = $this->createPayment();
        $payment->cancel($time);

        $this->expectException(PaymentClosedException::class);
        $payment->complete($time);
    }

    /**
     * @dataProvider getVariableSymbolUpdates
     */
    public function testUpdateVariableSymbol(?VariableSymbol $old, VariableSymbol $new)
    {
        $payment = $this->createPaymentWithVariableSymbol($old);
        $payment->extractEventsToDispatch(); // Clear events collection;

        $payment->updateVariableSymbol($new);
        $this->assertSame($new, $payment->getVariableSymbol());

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        /* @var $event PaymentVariableSymbolWasChanged */
        $event = $events[0];
        $this->assertInstanceOf(PaymentVariableSymbolWasChanged::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($new, $event->getVariableSymbol());
    }

    public function getVariableSymbolUpdates()
    {
        return [
            [new VariableSymbol('123'), new VariableSymbol('456')],
            [NULL, new VariableSymbol('456')],
        ];
    }

    public function testVariableSymbolUpdateToSameSymbolDoesntRaiseEvent()
    {
        $symbol = '12345';
        $payment = $this->createPaymentWithVariableSymbol(new VariableSymbol($symbol));
        $payment->extractEventsToDispatch(); // Clear events collection

        $payment->updateVariableSymbol(new VariableSymbol($symbol));

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(0, $events);
    }

    public function testUpdateVariableForClosedPaymentThrowsException()
    {
        $payment = $this->createPayment();
        $payment->cancel(new DateTimeImmutable());

        $this->expectException(PaymentClosedException::class);

        $payment->updateVariableSymbol(new VariableSymbol('789789'));
    }

    public function testUpdate()
    {
        $payment = $this->createPayment();
        $payment->extractEventsToDispatch(); // Clear events collection

        $name = "František Maša";
        $amount = 300;
        $email = "franta@gmail.com";
        $dueDate = new DateTimeImmutable();
        $variableSymbol = new VariableSymbol('789');
        $constantSymbol = 123;
        $note = "Never pays!";

        $payment->update($name, $email, $amount, $dueDate, $variableSymbol, $constantSymbol, $note);

        $this->assertSame($name, $payment->getName());
        $this->assertSame($email, $payment->getEmail());
        $this->assertSame((float)$amount, $payment->getAmount());
        $this->assertSame($dueDate, $payment->getDueDate());
        $this->assertSame($variableSymbol, $payment->getVariableSymbol());
        $this->assertSame($constantSymbol, $payment->getConstantSymbol());
        $this->assertSame($note, $payment->getNote());


        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        /* @var $event PaymentVariableSymbolWasChanged */
        $event = $events[0];
        $this->assertInstanceOf(PaymentVariableSymbolWasChanged::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($variableSymbol, $event->getVariableSymbol());
    }

    public function testUpdateWithSameVariableSymbolDoesntThrowException()
    {

    }

    public function getVariableSymbolChanges(): array
    {
        return [
            [new VariableSymbol('123'), new VariableSymbol('')]
        ];
    }

    public function testCannotUpdateClosedPayment()
    {
        $payment = $this->createPayment();

        $name = "František Maša";
        $amount = 300;
        $email = "franta@gmail.com";
        $dueDate = new DateTimeImmutable();
        $variableSymbol = new VariableSymbol('789');
        $constantSymbol = 123;
        $note = "Never pays!";

        $payment->complete(new DateTimeImmutable());

        $this->expectException(PaymentClosedException::class);

        $payment->update($name, $email, $amount, $dueDate, $variableSymbol, $constantSymbol, $note);
    }

    private function createPayment(): Payment
    {
        return $this->createPaymentWithVariableSymbol(new VariableSymbol('454545'));
    }

    private function createPaymentWithVariableSymbol(?VariableSymbol $symbol): Payment
    {

        $group = $this->mockGroup(29);
        $dueDate = new DateTimeImmutable();

        $payment = new Payment($group, "Jan novák", "test@gmail.com", 500, $dueDate, $symbol, 666, 454, "Some note");
        \Helpers::assignIdentity($payment, 1);

        return $payment;
    }

    private function mockGroup(int $id): Group
    {
        $group = m::mock(Group::class);
        $group->shouldReceive("getId")->andReturn($id);

        return $group;
    }

}
