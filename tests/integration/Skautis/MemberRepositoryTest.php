<?php

declare(strict_types=1);

namespace Model\Skautis;

use Hskauting\Tests\SkautisTest;
use Model\Common\UnitId;
use VCR\VCR;

final class MemberRepositoryTest extends SkautisTest
{
    public function testFindByUnitReturnsEmptyArrayIfNoMembersAreFound() : void
    {
        VCR::insertCassette('MemberRepository/findByUnit_empty.json');

        $members = $this->getRepository()->findByUnit(new UnitId(1), true);

        $this->assertCount(0, $members);
    }

    public function testFindByUnitReturnsAllMembers() : void
    {
        VCR::insertCassette('MemberRepository/findByUnit_allMembers.json');

        $members = $this->getRepository()->findByUnit(new UnitId(27267), true);

        $this->assertCount(2, $members);

        $this->assertSame(143986, $members[0]->getId());
        $this->assertSame('XYZ Markéta', $members[0]->getName());

        $this->assertSame(146478, $members[1]->getId());
        $this->assertSame('Kid Random', $members[1]->getName());
    }

    public function testFindByUnitReturnsDirectMembers() : void
    {
        VCR::insertCassette('MemberRepository/findByUnit_directMembers.json');

        $members = $this->getRepository()->findByUnit(new UnitId(27267), false);

        $this->assertCount(1, $members);

        $this->assertSame(146478, $members[0]->getId());
        $this->assertSame('Kid Random', $members[0]->getName());
    }

    private function getRepository() : MemberRepository
    {
        return new MemberRepository($this->createSkautis('11bf5c4e-a357-43c3-8510-7fd027d3687e'));
    }
}
