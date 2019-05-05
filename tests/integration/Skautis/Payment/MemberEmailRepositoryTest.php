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

        $this->assertCount(0, $this->getRepository()->findByMember(143550));
    }

    public function testFindByMemberReturnsListOfEmails() : void
    {
        VCR::insertCassette('Payment/MemberEmailRepository/findByMember.json');

        $this->assertSame(
            [
                'hlavni@email.cz' => 'hlavni@email.cz – E-mail (hlavní)',
                'dalsi@email.cz' => 'dalsi@email.cz – E-mail (další)',
                'matka@email.cz' => 'matka@email.cz – E-mail matky',
            ],
            $this->getRepository()->findByMember(143550)
        );
    }

    public function testReturnsEmptyListIfUserHasNoAccessToGivenMember() : void
    {
        VCR::insertCassette('Payment/MemberEmailRepository/findByMember_noPermission.json');

        $this->assertCount(0, $this->getRepository()->findByMember(1));
    }

    private function getRepository() : MemberEmailRepository
    {
        return new MemberEmailRepository($this->createSkautis('3eb6605b-4df9-431f-a8a7-3447c308ac77'));
    }
}
