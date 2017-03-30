<?php

namespace Model\Payment;

use DateTimeImmutable;

class GroupTest extends \Codeception\Test\Unit
{

    public function testCreate()
    {
        $dueDate = new DateTimeImmutable();
        $createdAt = new DateTimeImmutable();

        $group = new Group(
            NULL,
            20,
            NULL,
            "Skupina 01",
            200.2,
            $dueDate,
            203,
            666,
            $createdAt,
            "Email",
            NULL
        );

        $this->assertNull($group->getType());
        $this->assertSame(20, $group->getUnitId());
        $this->assertNull($group->getSkautisId());
        $this->assertSame("Skupina 01", $group->getName());
        $this->assertSame(200.2, $group->getDefaultAmount());
        $this->assertSame($dueDate, $group->getDueDate());
        $this->assertSame(203, $group->getConstantSymbol());
        $this->assertSame(666, $group->getNextVariableSymbol());
        $this->assertSame($createdAt, $group->getCreatedAt());
        $this->assertSame("Email", $group->getEmailTemplate());
        $this->assertNull($group->getSmtpId());
        $this->assertSame($group::STATE_OPEN, $group->getState());
        $this->assertSame("", $group->getNote());
    }

    public function testUpdate()
    {
        $dueDate = new DateTimeImmutable();
        $createdAt = new DateTimeImmutable();
        $group = $this->createGroup($dueDate, $createdAt);

        $group->update(
            "Skupina Jiná",
            120.0,
            NULL,
            NULL,
            NULL,
            "Email2",
            20
        );

        $this->assertNull($group->getType());
        $this->assertSame(20, $group->getUnitId());
        $this->assertNull($group->getSkautisId());
        $this->assertSame("Skupina Jiná", $group->getName());
        $this->assertSame(120.0, $group->getDefaultAmount());
        $this->assertNull($group->getDueDate());
        $this->assertNull($group->getConstantSymbol());
        $this->assertNull($group->getNextVariableSymbol());
        $this->assertSame($createdAt, $group->getCreatedAt());
        $this->assertSame("Email2", $group->getEmailTemplate());
        $this->assertSame(20, $group->getSmtpId());
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

    private function createGroup(?DateTimeImmutable $dueDate = NULL, ?DateTimeImmutable $createdAt = NULL): Group
    {
        return new Group(
            NULL,
            20,
            NULL,
            "Skupina 01",
            200.2,
            $dueDate ?? new DateTimeImmutable(),
            203,
            666,
            $createdAt ?? new DateTimeImmutable(),
            "Email",
            NULL
        );
    }

}
