<?php

namespace Model\Payment;

use DateTimeImmutable;
use Model\Payment\Group\EmailTemplate;
use Mockery as m;

class GroupTest extends \Codeception\Test\Unit
{

    public function testCreate()
    {
        $dueDate = new DateTimeImmutable();
        $createdAt = new DateTimeImmutable();
        $emailTemplate = new EmailTemplate("subject", "mail body");
        $group = new Group(
            20,
            NULL,
            "Skupina 01",
            200.2,
            $dueDate,
            203,
            666,
            $createdAt,
            $emailTemplate,
            NULL,
            m::mock(BankAccount::class, ['getId' => 23, 'getUnitId' => 20])
        );

        $this->assertSame(20, $group->getUnitId());
        $this->assertNull($group->getObject());
        $this->assertSame("Skupina 01", $group->getName());
        $this->assertSame(200.2, $group->getDefaultAmount());
        $this->assertSame($dueDate, $group->getDueDate());
        $this->assertSame(203, $group->getConstantSymbol());
        $this->assertSame(666, $group->getNextVariableSymbol());
        $this->assertSame($createdAt, $group->getCreatedAt());
        $this->assertSame($emailTemplate, $group->getEmailTemplate());
        $this->assertNull($group->getSmtpId());
        $this->assertSame($group::STATE_OPEN, $group->getState());
        $this->assertSame("", $group->getNote());
        $this->assertSame(23, $group->getBankAccountId());
    }

    public function testUpdate()
    {
        $dueDate = new DateTimeImmutable();
        $createdAt = new DateTimeImmutable();
        $group = $this->createGroup($dueDate, $createdAt);

        $emailTemplate = new EmailTemplate("subject2", "body2");

        $group->update(
            "Skupina Jiná",
            120.0,
            NULL,
            NULL,
            NULL,
            $emailTemplate,
            20,
            m::mock(BankAccount::class, ['getId' => 33, 'getUnitId' => 20])
        );

        $this->assertSame(20, $group->getUnitId());
        $this->assertNull($group->getObject());
        $this->assertSame("Skupina Jiná", $group->getName());
        $this->assertSame(120.0, $group->getDefaultAmount());
        $this->assertNull($group->getDueDate());
        $this->assertNull($group->getConstantSymbol());
        $this->assertNull($group->getNextVariableSymbol());
        $this->assertSame($createdAt, $group->getCreatedAt());
        $this->assertSame($emailTemplate, $group->getEmailTemplate());
        $this->assertSame(20, $group->getSmtpId());
        $this->assertSame(33, $group->getBankAccountId());
    }

    public function testClose()
    {
        $group = $this->createGroup();
        $note = "Closed because of ...";

        $group->close($note);

        $this->assertSame(Group::STATE_CLOSED, $group->getState());
        $this->assertSame($note, $group->getNote());
    }

    public function testReopen()
    {
        $group = $this->createGroup();
        $group->close("Closed because of ...");
        $note = "Reopend because someone didn't pay!";

        $group->open($note);

        $this->assertSame(Group::STATE_OPEN, $group->getState());
        $this->assertSame($note, $group->getNote());
    }

    public function testRemoveBankAccount()
    {
        $group = $this->createGroup(
            NULL,
            NULL,
            m::mock(BankAccount::class, ['getId' => 10, 'getUnitId' => 20])
        );

        $group->removeBankAccount();

        $this->assertNull($group->getBankAccountId());
    }

    private function createGroup(?DateTimeImmutable $dueDate = NULL, ?DateTimeImmutable $createdAt = NULL, BankAccount $bankAccount = NULL): Group
    {
        return new Group(
            20,
            NULL,
            "Skupina 01",
            200.2,
            $dueDate ?? new DateTimeImmutable(),
            203,
            666,
            $createdAt ?? new DateTimeImmutable(),
            new EmailTemplate("Email subject", "Email body"),
            NULL,
            $bankAccount
        );
    }

}
