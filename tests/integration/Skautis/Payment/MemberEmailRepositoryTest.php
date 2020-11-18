<?php

declare(strict_types=1);

namespace Model\Skautis\Payment;

use Hskauting\Tests\SkautisTest;
use VCR\VCR;

final class MemberEmailRepositoryTest extends SkautisTest
{
    public function testFindByMemberReturnsEmptyArrayIfUserHasNoContacts() : void
    {
        VCR::insertCassette('Payment/MemberEmailRepository/findByMember_empty.json');

        $this->assertCount(0, $this->getRepository()->findByMember(149580));
    }

    public function testFindByMemberReturnsListOfEmails() : void
    {
        VCR::insertCassette('Payment/MemberEmailRepository/findByMember.json');

        $this->assertSame(
            [
                'matka@ditetova.czz' => 'matka@ditetova.czz – Matka',
                'jan@dite.czz' => 'jan@dite.czz – E-mail (hlavní)',
            ],
            $this->getRepository()->findByMember(147006)
        );
    }

    public function testReturnsEmptyListIfUserHasNoAccessToGivenMember() : void
    {
        VCR::insertCassette('Payment/MemberEmailRepository/findByMember_noPermission.json');

        $this->assertCount(0, $this->getRepository()->findByMember(1));
    }

    private function getRepository() : MemberEmailRepository
    {
        return new MemberEmailRepository($this->createSkautis('b681d4f1-f1a9-453e-a3c7-c49fb4c20fd1'));
    }
}
