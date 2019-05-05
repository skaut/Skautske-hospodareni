<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Codeception\Test\Unit;
use Mockery;
use Model\Common\Member;
use Model\Common\Repositories\IMemberRepository;
use Model\Common\UnitId;
use Model\Payment\ReadModel\Queries\MembersWithoutPaymentInGroupQuery;
use Model\Payment\Repositories\IMemberEmailRepository;
use Model\PaymentService;

final class MembersWithoutPaymentInGroupQueryHandlerTest extends Unit
{
    public function testMembersWithPaymentAreNotReturnedAndReturnedMembersAreSortedByName() : void
    {
        $unitId  = new UnitId(1);
        $groupId = 5;

        $members = Mockery::mock(IMemberRepository::class);
        $members->shouldReceive('findByUnit')
            ->once()
            ->withArgs([$unitId, false])
            ->andReturn([
                new Member(1, 'Čenda'),
                new Member(2, 'Cibule'),
                new Member(3, 'Tom'),
            ]);

        $emailsByMember = [
            1 => ['cenda@email.cz' => 'email'],
            2 => ['cibule@email.cz' => 'email'],
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

        $result = $handler(new MembersWithoutPaymentInGroupQuery($unitId, $groupId));

        self::assertCount(2, $result);

        self::assertSame(2, $result[0]->getId());
        self::assertSame('Cibule', $result[0]->getName());

        self::assertSame(1, $result[1]->getId());
        self::assertSame('Čenda', $result[1]->getName());

        Mockery::close();
    }
}
