<?php

declare(strict_types=1);

namespace Model\Payment;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Mockery as m;
use Model\Payment\Group\PaymentDefaults;

class GroupTest extends Unit
{
    public function testCreate() : void
    {
        $dueDate         = new DateTimeImmutable('2018-01-19'); // friday
        $createdAt       = new DateTimeImmutable();
        $variableSymbol  = new VariableSymbol('666');
        $paymentDefaults = new PaymentDefaults(200.2, $dueDate, 203, $variableSymbol);
        $bankAccount     = m::mock(BankAccount::class, ['getId' => 23, 'getUnitId' => 20]);
        $emails          = \Helpers::createEmails();

        $group = new Group(20, null, 'Skupina 01', $paymentDefaults, $createdAt, $emails, null, $bankAccount);

        $this->assertSame(20, $group->getUnitId());
        $this->assertNull($group->getObject());
        $this->assertSame('Skupina 01', $group->getName());
        $this->assertSame(200.2, $group->getDefaultAmount());
        $this->assertSame($dueDate, $group->getDueDate());
        $this->assertSame(203, $group->getConstantSymbol());
        $this->assertSame($variableSymbol, $group->getNextVariableSymbol());
        $this->assertSame($createdAt, $group->getCreatedAt());
        $this->assertEmailsAreSame($emails, $group);
        $this->assertNull($group->getSmtpId());
        $this->assertSame($group::STATE_OPEN, $group->getState());
        $this->assertSame('', $group->getNote());
        $this->assertSame(23, $group->getBankAccountId());
    }

    public function testCreatingGroupWithNotAllEmailsThrowsException() : void
    {
        $paymentDefaults = new PaymentDefaults(null, null, null, null);
        $emails          = [];

        $this->expectException(\InvalidArgumentException::class);

        $group = new Group(1, null, 'Test', $paymentDefaults, new DateTimeImmutable(), $emails, null, null);
    }

    public function testUpdate() : void
    {
        $dueDate     = new DateTimeImmutable('2018-01-19'); // friday
        $createdAt   = new DateTimeImmutable();
        $group       = $this->createGroup($dueDate, $createdAt);
        $bankAccount = m::mock(BankAccount::class, ['getId' => 33, 'getUnitId' => 20]);

        $group->update('Skupina Jiná', new PaymentDefaults(120, null, null, null), 20, $bankAccount);

        $this->assertSame(20, $group->getUnitId());
        $this->assertNull($group->getObject());
        $this->assertSame('Skupina Jiná', $group->getName());
        $this->assertSame(120.0, $group->getDefaultAmount());
        $this->assertNull($group->getDueDate());
        $this->assertNull($group->getConstantSymbol());
        $this->assertNull($group->getNextVariableSymbol());
        $this->assertSame($createdAt, $group->getCreatedAt());
        $this->assertSame(20, $group->getSmtpId());
        $this->assertSame(33, $group->getBankAccountId());
    }

    public function testClose() : void
    {
        $group = $this->createGroup();
        $note  = 'Closed because of ...';

        $group->close($note);

        $this->assertSame(Group::STATE_CLOSED, $group->getState());
        $this->assertSame($note, $group->getNote());
    }

    public function testReopen() : void
    {
        $group = $this->createGroup();
        $group->close('Closed because of ...');
        $note = "Reopend because someone didn't pay!";

        $group->open($note);

        $this->assertSame(Group::STATE_OPEN, $group->getState());
        $this->assertSame($note, $group->getNote());
    }

    public function testRemoveBankAccount() : void
    {
        $group = $this->createGroup(
            null,
            null,
            m::mock(BankAccount::class, ['getId' => 10, 'getUnitId' => 20])
        );

        $group->removeBankAccount();

        $this->assertNull($group->getBankAccountId());
    }

    private function createGroup(?DateTimeImmutable $dueDate = null, ?DateTimeImmutable $createdAt = null, ?BankAccount $bankAccount = null) : Group
    {
        $dueDate         = $dueDate ?? new DateTimeImmutable('2018-01-19'); // defaults to friday
        $paymentDefaults = new PaymentDefaults(200.2, $dueDate, 203, new VariableSymbol('666'));
        $createdAt       = $createdAt ?? new DateTimeImmutable();
        $emails          = \Helpers::createEmails();

        return new Group(20, null, 'Skupina 01', $paymentDefaults, $createdAt, $emails, null, $bankAccount);
    }

    /**
     * @param EmailTemplate[] $expected
     */
    private function assertEmailsAreSame(array $expected, Group $group) : void
    {
        foreach ($expected as $key => $value) {
            $actual = $group->getEmailTemplate(EmailType::get($key));
            $this->assertTrue($value->equals($actual));
        }
    }
}
