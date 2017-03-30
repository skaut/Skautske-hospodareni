<?php

declare(strict_types=1);

namespace Model\Payment\Repositories;

use Kdyby\Doctrine\EntityManager;
use Model\Payment\Group;
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

    public function save(Group $group): void
    {
        $this->em->persist($group)->flush();
    }

}
