<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Payment;

use App\Model\Common\Services\EventBus;
use App\Model\Google\OAuthId;
use App\Model\Payment\DomainEvents\GroupWasRemoved;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\Group\Type;
use App\Model\Payment\GroupNotFound;
use App\Model\Payment\Repositories\IGroupRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

use function array_diff;
use function array_fill;
use function array_keys;
use function count;
use function implode;

final class GroupRepository implements IGroupRepository
{
    public function __construct(private EntityManager $em, private EventBus $eventBus)
    {
    }

    public function find(int $id): Group
    {
        $group = $this->em->find(Group::class, $id);

        if (! $group instanceof Group) {
            throw new GroupNotFound();
        }

        return $group;
    }

    public function findByIds(array $ids): array
    {
        $groups = $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g', 'g.id')
            ->where('g.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        if (count($ids) !== count($groups)) {
            throw new GroupNotFound('Groups with id '.implode(', ', array_diff($ids, array_keys($groups))));
        }

        return $groups;
    }

    public function findByReminder(): array
    {
        return $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g', 'g.id')
            ->leftJoin(Group\Email::class, 'e', Join::WITH, 'e.group = g')
            ->where('g.isRemindersEnabled = 1')
            ->andWhere('e.type = :reminder')
            ->andWhere('e.enabled = 1')
            ->setParameter('reminder', EmailType::PAYMENT_REMINDER)
            ->getQuery()
            ->getResult();
    }

    public function findByUnits(array $unitIds, bool $openOnly): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->join('g.units', 'u', Join::WITH, 'u.unitId IN (:unitIds)')
            ->setParameter('unitIds', $unitIds);

        if ($openOnly) {
            $qb->andWhere('g.state = :state')
                ->setParameter('state', Group::STATE_OPEN);
        }

        return $qb->getQuery()->getResult();
    }

    public function findBySkautisEntities(Group\SkautisEntity ...$objects): array
    {
        if (count($objects) === 0) {
            return [];
        }

        // DQL does not support tuples with IN expression, so SQL + result set mapping is used
        // see https://github.com/doctrine/orm/issues/7206
        $resultSetMapping = new ResultSetMappingBuilder($this->em);
        $resultSetMapping->addRootEntityFromClassMetadata(Group::class, 'g');

        $sql = 'SELECT g.* FROM pa_group g '
            .'WHERE (g.sisId, g.groupType) IN ('.implode(', ', array_fill(0, count($objects), '(?, ?)'))
            .') ORDER BY g.id';

        $parameters = [];
        foreach ($objects as $object) {
            $parameters[] = $object->getId();
            $parameters[] = $object->getType()->getValue();
        }

        return $this->em->createNativeQuery($sql, $resultSetMapping)
            ->setParameters($parameters)
            ->getResult();
    }

    public function findBySkautisEntityType(Type $type): array
    {
        return $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.object.type = :type')
            ->setParameter('type', $type->getValue())
            ->getQuery()
            ->getResult();
    }

    public function findByBankAccount(int $bankAccountId): array
    {
        return $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.bankAccount.id = :bankAccountId')
            ->setParameter('bankAccountId', $bankAccountId)
            ->getQuery()
            ->getResult();
    }

    public function findByOAuth(OAuthId $oAuthId): array
    {
        return $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.oauthId = :oauthId')
            ->setParameter('oauthId', $oAuthId)
            ->getQuery()
            ->getResult();
    }

    public function save(Group $group): void
    {
        $this->em->persist($group);
        $this->em->flush();
    }

    public function remove(Group $group): void
    {
        $this->em->wrapInTransaction(
            function (EntityManager $entityManager) use ($group): void {
                $entityManager->remove($group);

                $this->eventBus->handle(new GroupWasRemoved($group->getId()));

                $entityManager->flush();
            },
        );
    }
}
