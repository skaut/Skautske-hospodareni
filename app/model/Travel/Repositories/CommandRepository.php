<?php

namespace Model\Travel\Repositories;

use Kdyby\Doctrine\EntityManager;
use Model\Travel\Command;

class CommandRepository implements ICommandRepository
{

    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function countByVehicle(int $vehicleId): int
    {
        return $this->em->getRepository(Command::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->where('IDENTITY(c.vehicle) = :vehicleId')
            ->andWhere('c.deleted = FALSE')
            ->setParameter('vehicleId', $vehicleId)
            ->getQuery()
            ->getSingleScalarResult();
    }

}
