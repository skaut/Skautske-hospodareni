<?php

declare(strict_types=1);

namespace App\Model\User\Repository;

use App\Model\Payment\Group;
use App\Model\User\Entity\PaymentGroupVisit;
use App\Model\User\Manager\PaymentGroupVisitManager;
use IntegrationTest;

class PaymentGroupVisitRepositoryTest extends IntegrationTest
{
    private PaymentGroupVisitRepository $repository;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [
            Group::class,
            PaymentGroupVisit::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);

        parent::_before();

        $this->repository = new PaymentGroupVisitRepository($this->entityManager);
    }

    public function testFindRecentlyVisitedGroupsReturnsAccessibleGroupsForUserOrderedByVisit(): void
    {
        $this->createGroup(1, 10);
        $this->createGroup(2, 10);
        $this->createGroup(3, 10);
        $this->createGroup(4, 10);
        $this->createGroup(5, 99);

        $this->createVisit(2465, 1, '2026-07-07 10:00:00');
        $this->createVisit(2465, 2, '2026-07-07 12:00:00');
        $this->createVisit(2465, 3, '2026-07-07 11:00:00');
        $this->createVisit(1942, 4, '2026-07-07 13:00:00');
        $this->createVisit(2465, 5, '2026-07-07 14:00:00');

        $groups = $this->repository->findRecentlyVisitedGroups(2465, [10], 3);

        self::assertSame([2, 3, 1], array_map(static fn (Group $group): int => (int) $group->getId(), $groups));
    }

    public function testDeleteVisitsOverLimitKeepsOnlyNewestUserVisits(): void
    {
        $this->createGroup(1, 10);
        $this->createGroup(2, 10);
        $this->createGroup(3, 10);
        $this->createGroup(4, 10);
        $this->createGroup(5, 10);

        $this->createVisit(2465, 1, '2026-07-07 10:00:00');
        $this->createVisit(2465, 2, '2026-07-07 11:00:00');
        $this->createVisit(2465, 3, '2026-07-07 12:00:00');
        $this->createVisit(2465, 4, '2026-07-07 13:00:00');
        $this->createVisit(1942, 5, '2026-07-07 14:00:00');

        $this->repository->deleteVisitsOverLimit(2465, 3);

        self::assertSame(3, (int) $this->tester->grabNumRecords('payment_group_visit', ['user_id' => 2465]));
        $this->tester->dontSeeInDatabase('payment_group_visit', ['user_id' => 2465, 'group_id' => 1]);
        $this->tester->seeInDatabase('payment_group_visit', ['user_id' => 2465, 'group_id' => 2]);
        $this->tester->seeInDatabase('payment_group_visit', ['user_id' => 2465, 'group_id' => 3]);
        $this->tester->seeInDatabase('payment_group_visit', ['user_id' => 2465, 'group_id' => 4]);
        $this->tester->seeInDatabase('payment_group_visit', ['user_id' => 1942, 'group_id' => 5]);
    }

    public function testManagerKeepsOnlyThreeVisitsPerUser(): void
    {
        $manager = new PaymentGroupVisitManager($this->entityManager, $this->repository);

        $this->createGroup(1, 10);
        $this->createGroup(2, 10);
        $this->createGroup(3, 10);
        $this->createGroup(4, 10);

        $manager->markVisited(2465, 1);
        $manager->markVisited(2465, 2);
        $manager->markVisited(2465, 3);
        $manager->markVisited(2465, 4);

        self::assertSame(3, (int) $this->tester->grabNumRecords('payment_group_visit', ['user_id' => 2465]));
        $this->tester->dontSeeInDatabase('payment_group_visit', ['user_id' => 2465, 'group_id' => 1]);
        $this->tester->seeInDatabase('payment_group_visit', ['user_id' => 2465, 'group_id' => 2]);
        $this->tester->seeInDatabase('payment_group_visit', ['user_id' => 2465, 'group_id' => 3]);
        $this->tester->seeInDatabase('payment_group_visit', ['user_id' => 2465, 'group_id' => 4]);
    }

    private function createGroup(int $id, int $unitId): void
    {
        $this->tester->haveInDatabase('pa_group', [
            'id' => $id,
            'name' => 'Test '.$id,
            'state' => Group::STATE_OPEN,
            'note' => '',
            'created_at' => '2026-07-07 09:00:00',
            'last_pairing' => null,
            'oauth_id' => null,
            'bank_account_id' => null,
            'amount' => 100.0,
            'next_variable_symbol' => '100',
            'due_date' => '2026-07-08',
            'constant_symbol' => null,
        ]);

        $this->tester->haveInDatabase('pa_group_unit', [
            'group_id' => $id,
            'unit_id' => $unitId,
        ]);
    }

    private function createVisit(int $userId, int $groupId, string $visitedAt): void
    {
        $this->tester->haveInDatabase('payment_group_visit', [
            'user_id' => $userId,
            'group_id' => $groupId,
            'visited_at' => $visitedAt,
        ]);
    }
}
