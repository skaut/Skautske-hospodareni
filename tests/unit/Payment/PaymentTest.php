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

}
