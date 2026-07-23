<?php

declare(strict_types=1);

namespace App\Model\User\Repository;

use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\Payment\Group;
use App\Model\User\Entity\PaymentGroupVisit;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

use function array_map;

/** @extends AbstractRepository<PaymentGroupVisit> */
class PaymentGroupVisitRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return PaymentGroupVisit::class;
    }

    public function findOneByUserIdAndGroupId(int $userId, int $groupId): ?PaymentGroupVisit
    {
        /** @var PaymentGroupVisit|null $visit */
        $visit = $this->findOneBy(['userId' => $userId, 'groupId' => $groupId]);

        return $visit;
    }

    /**
     * @param int[] $unitIds
     *
     * @return Group[]
     */
    public function findRecentlyVisitedGroups(int $userId, array $unitIds, int $limit): array
    {
        if ($unitIds === [] || $limit < 1) {
            return [];
        }

        $groupIds = $this->entityManager->getConnection()
            ->executeQuery(
                'SELECT v.group_id
                    FROM payment_group_visit v
                    WHERE v.user_id = ?
                        AND EXISTS (
                            SELECT 1
                            FROM pa_group_unit gu
                            WHERE gu.group_id = v.group_id
                                AND gu.unit_id IN (?)
                        )
                    ORDER BY v.visited_at DESC, v.id DESC
                    LIMIT ?',
                [$userId, $unitIds, $limit],
                [ParameterType::INTEGER, Connection::PARAM_INT_ARRAY, ParameterType::INTEGER],
            )
            ->fetchFirstColumn();
        $groupIds = array_map('intval', $groupIds);

        if ($groupIds === []) {
            return [];
        }

        /** @var array<int, Group> $groupsById */
        $groupsById = $this->entityManager->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g', 'g.id')
            ->where('g.id IN (:groupIds)')
            ->setParameter('groupIds', $groupIds)
            ->getQuery()
            ->getResult();

        $groups = [];
        foreach ($groupIds as $groupId) {
            if (isset($groupsById[$groupId])) {
                $groups[] = $groupsById[$groupId];
            }
        }

        return $groups;
    }

    public function deleteVisitsOverLimit(int $userId, int $limit): void
    {
        if ($limit < 1) {
            $this->entityManager->getConnection()->delete('payment_group_visit', ['user_id' => $userId]);

            return;
        }

        $this->entityManager->getConnection()
            ->executeStatement(
                'DELETE FROM payment_group_visit
                    WHERE user_id = ?
                        AND id NOT IN (
                            SELECT id FROM (
                                SELECT id
                                FROM payment_group_visit
                                WHERE user_id = ?
                                ORDER BY visited_at DESC, id DESC
                                LIMIT ?
                            ) recent_visits
                        )',
                [$userId, $userId, $limit],
                [ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::INTEGER],
            );
    }
}
