<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Common\Member;
use App\Model\Common\Repositories\IMemberRepository;
use App\Model\Common\UnitId;
use App\Model\DTO\Payment\MemberEmail;
use App\Model\DTO\Payment\MemberEmailType;
use App\Model\Payment\PaymentService;
use App\Model\Payment\ReadModel\Queries\MembersWithoutPaymentInGroupQuery;
use App\Model\Payment\Repositories\IMemberEmailRepository;
use Codeception\Test\Unit;
use Mockery;

final class MembersWithoutPaymentInGroupQueryHandlerTest extends Unit
{
    public function testMembersWithPaymentAreNotReturned(): void
    {
        $unitId = new UnitId(1);
        $groupId = 5;

        $members = Mockery::mock(IMemberRepository::class);
        $members->shouldReceive('findByUnit')
            ->once()
            ->withArgs([$unitId, false])
            ->andReturn([
                new Member(2, 'Cibule', null),
                new Member(1, 'Čenda', null),
                new Member(3, 'Tom', null),
            ]);

        $emailsByMember = [
            1 => [new MemberEmail('cenda@email.cz', 'email', MemberEmailType::MAIN)],
            2 => [new MemberEmail('cibule@email.cz', 'email', MemberEmailType::MAIN)],
        ];

        $emails = Mockery::mock(IMemberEmailRepository::class);

        foreach ($emailsByMember as $memberId => $memberEmails) {
            $emails->shouldReceive('findByMember')
                ->once()
                ->withArgs([$memberId])
                ->andReturn($memberEmails);
        }

        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('getPersonsWithActivePayment')
            ->once()
            ->withArgs([$groupId])
            ->andReturn([3]);

        $handler = new MembersWithoutPaymentInGroupQueryHandler($members, $emails, $paymentService);

        $result = $handler(new MembersWithoutPaymentInGroupQuery($unitId, $groupId, true));

        self::assertCount(2, $result);

        self::assertSame(2, $result[0]->getId());
        self::assertSame('Cibule', $result[0]->getName());

        self::assertSame(1, $result[1]->getId());
        self::assertSame('Čenda', $result[1]->getName());
    }
}
