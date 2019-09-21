<?php

declare(strict_types=1);

namespace Model\User;

use Codeception\Test\Unit;

final class SkautisRoleTest extends Unit
{
    public function testGetUnitId() : void
    {
        $role = new SkautisRole('vedouciStredisko', '', 123);

        $this->assertSame(123, $role->getUnitId());
    }

    public function testBasicUnitLeader() : void
    {
        $role = new SkautisRole('vedouciStredisko', '', 123);

        $this->assertTrue($role->isLeader());
        $this->assertFalse($role->isAccountant());
        $this->assertFalse($role->isOfficer());
        $this->assertFalse($role->isEventManager());

        $this->assertTrue($role->isBasicUnit());
        $this->assertFalse($role->isTroop());
    }

    public function testBasicUnitAccountant() : void
    {
        $role = new SkautisRole('hospodarStredisko', '', 123);

        $this->assertFalse($role->isLeader());
        $this->assertTrue($role->isAccountant());
        $this->assertFalse($role->isOfficer());
        $this->assertFalse($role->isEventManager());

        $this->assertTrue($role->isBasicUnit());
        $this->assertFalse($role->isTroop());
    }

    public function testTroopOfficer() : void
    {
        $role = new SkautisRole('cinovnikOddil', '', 123);

        $this->assertFalse($role->isLeader());
        $this->assertFalse($role->isAccountant());
        $this->assertTrue($role->isOfficer());
        $this->assertFalse($role->isEventManager());

        $this->assertFalse($role->isBasicUnit());
        $this->assertTrue($role->isTroop());
    }

    public function testEventManager() : void
    {
        $role = new SkautisRole('spravceAkci', '', 123);

        $this->assertFalse($role->isLeader());
        $this->assertFalse($role->isAccountant());
        $this->assertFalse($role->isOfficer());
        $this->assertTrue($role->isEventManager());
    }

    public function testEmptyString() : void
    {
        $role = new SkautisRole('', '', 123);

        $this->assertFalse($role->isLeader());
        $this->assertFalse($role->isAccountant());
        $this->assertFalse($role->isOfficer());
        $this->assertFalse($role->isEventManager());

        $this->assertFalse($role->isBasicUnit());
        $this->assertFalse($role->isTroop());
    }

    public function testGetName() : void
    {
        $role = new SkautisRole('', 'Vedoucí', 123);

        $this->assertSame('Vedoucí', $role->getName());
    }
}
