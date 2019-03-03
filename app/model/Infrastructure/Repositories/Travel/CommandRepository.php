<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use Doctrine\ORM\EntityManager;
use Model\Travel\Command;
use Model\Travel\CommandNotFound;
use Model\Travel\Repositories\ICommandRepository;

final class CommandRepository implements ICommandRepository
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function find(int $id) : Command
    {
        $command = $this->em->find(Command::class, $id);

        if ($command === null) {
            throw new CommandNotFound();
        }

        return $command;
    }

    /**
     * @return Command[]
     */
    public function findByUnit(int $unitId) : array
    {
        return $this->em->getRepository(Command::class)->findBy(['unitId' => $unitId]);
    }

    /**
     * @return Command[]
     */
    public function findByUnitAndUser(int $unitId, int $userId) : array
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Command::class, 'c')
            ->where('c.unitId = :unitId')
            ->orWhere('c.ownerId = :userId')
            ->setParameter('unitId', $unitId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Command[]
     */
    public function findByVehicle(int $vehicleId) : array
    {
        return $this->em->getRepository(Command::class)->findBy(['vehicle' => $vehicleId]);
    }

    /**
     * @return Command[]
     */
    public function findByContract(int $contractId) : array
    {
        return $this->em->createQueryBuilder()
            ->select('c')
            ->from(Command::class, 'c')
            ->where('c.passenger.contractId = :contractId')
            ->addOrderBy('c.closedAt')
            ->addOrderBy('c.id', 'DESC')
            ->setParameter('contractId', $contractId)
            ->getQuery()
            ->getResult();
    }

    public function countByVehicle(int $vehicleId) : int
    {
        return (int) $this->em->getRepository(Command::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->where('IDENTITY(c.vehicle) = :vehicleId')
            ->setParameter('vehicleId', $vehicleId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function remove(Command $command) : void
    {
        $this->em->remove($command);
        $this->em->flush();
    }

    public function save(Command $command) : void
    {
        $this->em->persist($command);
        $this->em->flush();
    }
}
