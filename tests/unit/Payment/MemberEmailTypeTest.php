<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\DTO\Payment\MemberEmailType;
use Codeception\Test\Unit;

final class MemberEmailTypeTest extends Unit
{
    public function testMapsSkautisContactTypes(): void
    {
        self::assertSame(MemberEmailType::MAIN, MemberEmailType::fromSkautis('email_hlavni'));
        self::assertSame(MemberEmailType::OTHER, MemberEmailType::fromSkautis('email_dalsi'));
        self::assertSame(MemberEmailType::OTHER, MemberEmailType::fromSkautis('email_jiny'));
        self::assertSame(MemberEmailType::OTHER, MemberEmailType::fromSkautis('email_neznamy', null, 'E-mail (další)'));
        self::assertSame(MemberEmailType::FATHER, MemberEmailType::fromSkautis('email_hlavni', 'father'));
        self::assertSame(MemberEmailType::MOTHER, MemberEmailType::fromSkautis('email_hlavni', 'mother'));
        self::assertSame(MemberEmailType::UNKNOWN, MemberEmailType::fromSkautis('email_pracovni'));
        self::assertSame(MemberEmailType::UNKNOWN, MemberEmailType::fromSkautis('email_hlavni', 'guardian'));
    }
}
