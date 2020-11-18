<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Payment;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use eGen\MessageBus\Bus\EventBus;
use Kdyby\Doctrine\Dql\Join;
use Model\Google\OAuthId;
use Model\Payment\DomainEvents\GroupWasRemoved;
use Model\Payment\Group;
use Model\Payment\Group\Type;
use Model\Payment\GroupNotFound;
use Model\Payment\Repositories\IGroupRepository;
use function array_diff;
use function array_fill;
use function array_keys;
use function count;
use function implode;

final class GroupRepository implements IGroupRepository
{
    /** @var EntityManager */
    private $em;

    /** @var EventBus */
    private $eventBus;

    public function __construct(EntityManager $em, EventBus $eventBus)
    {
        $this->em       = $em;
        $this->eventBus = $eventBus;
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id) : Group
    {
        $group = $this->em->find(Group::class, $id);

        if (! $group instanceof Group) {
            throw new GroupNotFound();
        }

        return $group;
    }

    /**
     * {@inheritDoc}
     */
    public function findByIds(array $ids) : array
    {
        $groups = $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g', 'g.id')
            ->where('g.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        if (count($ids) !== count($groups)) {
            throw new GroupNotFound('Groups with id ' . implode(', ', array_diff($ids, array_keys($groups))));
        }

        return $groups;
    }

    /**
     * {@inheritDoc}
     */
    public function findByUnits(array $unitIds, bool $openOnly) : array
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

    /**
     * {@inheritDoc}
     */
    public function findBySkautisEntities(Group\SkautisEntity ...$objects) : array
    {
        if (count($objects) === 0) {
            return [];
        }

        // DQL does not support tuples with IN expression, so SQL + result set mapping is used
        // see https://github.com/doctrine/orm/issues/7206
        $resultSetMapping = new ResultSetMappingBuilder($this->em);
        $resultSetMapping->addRootEntityFromClassMetadata(Group::class, 'g');

        $sql = 'SELECT g.* FROM pa_group g '
            . 'WHERE (g.sisId, g.groupType) IN (' . implode(', ', array_fill(0, count($objects), '(?, ?)'))
            . ') ORDER BY g.id';

        $parameters = [];
        foreach ($objects as $object) {
            $parameters[] = $object->getId();
            $parameters[] = $object->getType()->getValue();
        }

        return $this->em->createNativeQuery($sql, $resultSetMapping)
            ->setParameters($parameters)
            ->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findBySkautisEntityType(Type $type) : array
    {
        return $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.object.type = :type')
            ->setParameter('type', $type->getValue())
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findByBankAccount(int $bankAccountId) : array
    {
        return $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.bankAccount.id = :bankAccountId')
            ->setParameter('bankAccountId', $bankAccountId)
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findByOAuth(OAuthId $oAuthId) : array
    {
        return $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.oauthId = :oauthId')
            ->setParameter('oauthId', $oAuthId)
            ->getQuery()
            ->getResult();
    }

    public function save(Group $group) : void
    {
        $this->em->persist($group);
        $this->em->flush();
    }

    public function remove(Group $group) : void
    {
        $this->em->transactional(
            function (EntityManager $entityManager) use ($group) : void {
                $entityManager->remove($group);

                $this->eventBus->handle(new GroupWasRemoved($group->getId()));

                $entityManager->flush();
            }
        );
    }
}
