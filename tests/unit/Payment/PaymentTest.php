<?php

namespace Model\Payment;

use DateTimeImmutable;
use Mockery as m;
use Model\Payment\DomainEvents\PaymentWasCreated;
use Model\Payment\Payment\State;

class PaymentTest extends \Codeception\Test\Unit
{


    public function testCreate(): void
    {
        $group = m::mock(Group::class);
        $group->shouldReceive("getId")->andReturn(29);

        $name = "Jan novák";
        $email = "test@gmail.com";
        $dueDate = new DateTimeImmutable();
        $amount = 450;
        $variableSymbol = 454545;
        $constantSymbol = 666;
        $personId = 2;
        $note = 'Something';

        $payment = new Payment(
            $group,
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
        $this->assertSame(29, $payment->getGroupId());

        $events = $payment->extractEventsToDispatch();
        $this->assertCount(1, $events);
        /* @var $event PaymentWasCreated */
        $event = $events[0];
        $this->assertInstanceOf(PaymentWasCreated::class, $event);
        $this->assertSame(29, $event->getGroupId());
        $this->assertSame($variableSymbol, $event->getVariableSymbol());
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

    public function testUpdateVariableSymbol()
    {
        $payment = $this->createPayment();
        $payment->updateVariableSymbol(789789);
        $this->assertSame(789789, $payment->getVariableSymbol());
    }

    public function testUpdateVariableForClosedPaymentThrowsException()
    {
        $payment = $this->createPayment();
        $payment->cancel(new DateTimeImmutable());

        $this->expectException(PaymentClosedException::class);

        $payment->updateVariableSymbol(789789);
    }

    public function testUpdate()
    {
        $payment = $this->createPayment();

        $name = "František Maša";
        $amount = 300;
        $email = "franta@gmail.com";
        $dueDate = new DateTimeImmutable();
        $variableSymbol = 789;
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
    }

    public function testCannotUpdateClosedPayment()
    {
        $payment = $this->createPayment();

        $name = "František Maša";
        $amount = 300;
        $email = "franta@gmail.com";
        $dueDate = new DateTimeImmutable();
        $variableSymbol = 789;
        $constantSymbol = 123;
        $note = "Never pays!";

        $payment->complete(new DateTimeImmutable());

        $this->expectException(PaymentClosedException::class);

        $payment->update($name, $email, $amount, $dueDate, $variableSymbol, $constantSymbol, $note);
    }

    private function createPayment(): Payment
    {
        $group = m::mock(Group::class);
        $group->shouldReceive("getId")->andReturn(29);

        return new Payment(
            $group,
            "Jan novák",
            "test@gmail.com",
            500,
            new DateTimeImmutable(),
            454545,
            666,
            454,
            "Some note"
        );

    }

}
