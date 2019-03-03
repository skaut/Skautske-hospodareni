<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use Doctrine\ORM\EntityManager;
use Model\Travel\Repositories\IVehicleRepository;
use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFound;
use function array_values;

final class VehicleRepository implements IVehicleRepository
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @throws VehicleNotFound
     */
    public function find(int $id) : Vehicle
    {
        $vehicle = $this->em->find(Vehicle::class, $id);

        if (! $vehicle instanceof Vehicle) {
            throw new VehicleNotFound();
        }

        return $vehicle;
    }

    /**
     * @param int[] $ids
     * @return Vehicle[]
     */
    public function findByIds(array $ids) : array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->em->createQueryBuilder()
            ->select('v')
            ->from(Vehicle::class, 'v', 'v.id')
            ->where('v.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Vehicle[]
     */
    public function findByUnit(int $unitId) : array
    {
        $vehicles = $this->em->createQueryBuilder()
            ->select('v')
            ->from(Vehicle::class, 'v', 'v.id')
            ->where('v.unitId = :unitId')
            ->andWhere('v.archived = FALSE')
            ->setParameter('unitId', $unitId)
            ->getQuery()
            ->getResult();

        return array_values($vehicles);
    }

    public function save(Vehicle $vehicle) : void
    {
        $this->em->persist($vehicle);
        $this->em->flush();
    }

    public function remove(Vehicle $vehicle) : void
    {
        $this->em->remove($vehicle);
        $this->em->flush();
    }
}
