<?php

namespace Model\Payment;

use DateTimeImmutable;
use Mockery as m;
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

        $this->expectException(PaymentFinishedException::class);
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

        $this->expectException(PaymentFinishedException::class);
        $payment->complete($time);
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
