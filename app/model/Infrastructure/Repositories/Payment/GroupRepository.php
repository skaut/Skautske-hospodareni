<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Payment;

use Doctrine\ORM\EntityManager;
use eGen\MessageBus\Bus\EventBus;
use Model\Payment\DomainEvents\GroupWasRemoved;
use Model\Payment\Group;
use Model\Payment\Group\Type;
use Model\Payment\GroupNotFound;
use Model\Payment\Repositories\IGroupRepository;
use function array_diff;
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
            ->where('g.unitId IN (:unitIds)')
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
    public function findBySkautisEntity(Group\SkautisEntity $object) : array
    {
        return $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.object.id = :skautisId')
            ->andWhere('g.object.type = :type')
            ->setParameter('skautisId', $object->getId())
            ->setParameter('type', $object->getType())
            ->getQuery()
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
