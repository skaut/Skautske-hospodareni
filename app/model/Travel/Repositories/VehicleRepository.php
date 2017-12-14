<?php

namespace Model\Travel\Repositories;

use Consistence\Type\ArrayType\ArrayType;
use Consistence\Type\ArrayType\KeyValuePair;
use Doctrine\ORM\EntityManager;
use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFoundException;

class VehicleRepository implements IVehicleRepository
{

    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @throws VehicleNotFoundException
     */
    public function find(int $id): Vehicle
    {
        $vehicle = $this->em->find(Vehicle::class, $id);

        if(! $vehicle instanceof Vehicle) {
            throw new VehicleNotFoundException();
        }

        return $vehicle;
    }

    public function findByIds(array $ids): array
    {
        if(empty($ids)) {
            return [];
        }

        return $this->em->createQueryBuilder()
            ->select("v")
            ->from(Vehicle::class, "v", "v.id")
            ->where("v.id IN (:ids)")
            ->setParameter("ids", $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Vehicle[]
     */
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

        if(empty($vehicles)) {
            return [];
        }

        return $vehicles;
    }

    public function getPairs(int $unitId): array
    {
        $vehicles = $this->em->getRepository(Vehicle::class)->findBy([
            'unitId' => $unitId,
            'archived' => FALSE,
        ]);

        return ArrayType::mapByCallback($vehicles, function(KeyValuePair $pair) {
            $value = $pair->getValue();
            /** @var Vehicle $value */
            return new KeyValuePair($value->getId(), $value->getLabel());
        });

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
