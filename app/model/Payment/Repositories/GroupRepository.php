<?php

declare(strict_types=1);

namespace Model\Payment\Repositories;

use Assert\Assert;
use Kdyby\Doctrine\Connection;
use Doctrine\ORM\EntityManager;
use Model\Payment\Group;
use Model\Payment\Group\Type;
use Model\Payment\GroupNotFoundException;

class GroupRepository implements IGroupRepository
{

    /** @var EntityManager */
    private $em;


    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param int $id
     * @return Group
     * @throws GroupNotFoundException
     */
    public function find(int $id): Group
    {
        $group = $this->em->find(Group::class, $id);

        if(! $group instanceof Group) {
            throw new GroupNotFoundException();
        }

        return $group;
    }

    public function findByIds(array $ids): array
    {
        Assert::thatAll($ids)->integer();

        $groups = $this->em->createQueryBuilder()
            ->select("g")
            ->from(Group::class, "g", "g.id")
            ->where("g.id IN (:ids)")
            ->setParameter("ids", $ids, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getResult();

        if(count($ids) !== count($groups)) {
            throw new GroupNotFoundException("Groups with id " . implode(", ", array_diff($ids, array_keys($groups))));
        }

        return $groups;
    }

    public function findByUnits(array $unitIds, bool $openOnly): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.unitId IN (:unitIds)')
            ->setParameter('unitIds', $unitIds, Connection::PARAM_INT_ARRAY);

        if($openOnly) {
            $qb->andWhere('g.state = :state')
                ->setParameter('state', Group::STATE_OPEN);
        }

        return $qb->getQuery()->getResult();
    }

    public function findBySkautisEntity(Group\SkautisEntity $object): array
    {
        return $this->em->createQueryBuilder()
            ->select("g")
            ->from(Group::class, "g")
            ->where("g.object.id = :skautisId")
            ->andWhere("g.object.type = :type")
            ->setParameter("skautisId", $object->getId())
            ->setParameter("type", $object->getType())
            ->getQuery()
            ->getResult();
    }

    public function findBySkautisEntityType(Type $type): array
    {
        return $this->em->createQueryBuilder()
            ->select("g")
            ->from(Group::class, "g")
            ->where("g.object.type = :type")
            ->setParameter("type", $type->getValue())
            ->getQuery()
            ->getResult();
    }

    public function save(Group $group): void
    {
        $this->em->persist($group);
        $this->em->flush();
    }

}
