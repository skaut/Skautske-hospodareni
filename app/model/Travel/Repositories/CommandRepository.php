<?php

namespace Model\Travel\Repositories;

use Doctrine\ORM\EntityManager;
use Model\Travel\Command;
use Model\Travel\CommandNotFoundException;

class CommandRepository implements ICommandRepository
{

    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function find(int $id): Command
    {
        $command = $this->em->find(Command::class, $id);

        if($command === NULL) {
            throw new CommandNotFoundException();
        }

        return $command;
    }

    public function findByUnit(int $unitId): array
    {
        return $this->em->getRepository(Command::class)->findBy(["unitId" => $unitId]);
    }

    public function countByVehicle(int $vehicleId): int
    {
        return $this->em->getRepository(Command::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->where('IDENTITY(c.vehicle) = :vehicleId')
            ->setParameter('vehicleId', $vehicleId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function remove(Command $command): void
    {
        $this->em->remove($command);
        $this->em->flush();
    }

    public function save(Command $command): void
    {
        $this->em->persist($command);
        $this->em->flush();
    }

}
