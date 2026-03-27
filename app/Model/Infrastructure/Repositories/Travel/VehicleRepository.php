<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Travel;

use App\Model\Travel\Repositories\IVehicleRepository;
use App\Model\Travel\Vehicle;
use App\Model\Travel\VehicleNotFound;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use function array_values;

final class VehicleRepository implements IVehicleRepository
{
    public function __construct(private EntityManager $em)
    {
    }

    /** @throws VehicleNotFound */
    public function find(int $id): Vehicle
    {
        $vehicle = $this->em->find(Vehicle::class, $id);

        if (! $vehicle instanceof Vehicle) {
            throw new VehicleNotFound();
        }

        return $vehicle;
    }

    /**
     * @param int[] $ids
     *
     * @return Vehicle[]
     */
    public function findByIds(array $ids): array
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

    /** @return Vehicle[] */
    public function findByUnit(int $unitId): array
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

    public function findByFilter(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('v')
            ->from(Vehicle::class, 'v', 'v.id')
            ->where('v.archived = FALSE');
    }

    public function save(Vehicle $vehicle): void
    {
        $this->em->persist($vehicle);
        $this->em->flush();
    }

    public function remove(Vehicle $vehicle): void
    {
        $this->em->remove($vehicle);
        $this->em->flush();
    }
}
