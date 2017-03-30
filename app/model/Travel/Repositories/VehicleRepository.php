<?php

namespace Model\Travel\Repositories;

use Consistence\Type\ArrayType\ArrayType;
use Consistence\Type\ArrayType\KeyValuePair;
use Kdyby\Doctrine\EntityManager;
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
     * @param int $id
     * @throws VehicleNotFoundException
     * @return Vehicle
     */
    public function get($id)
    {
        $vehicle = $this->em->find(Vehicle::class, $id);

        if(! $vehicle instanceof Vehicle) {
            throw new VehicleNotFoundException();
        }

        return $vehicle;
    }

    /**
     * @param $unitId
     * @return Vehicle[]
     */
    public function getAll($unitId)
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

    /**
     * @param int $unitId
     * @return array
     */
    public function getPairs($unitId)
    {
        $vehicles = $this->em->getRepository(Vehicle::class)->findBy([
            'unitId' => $unitId,
            'archived' => FALSE,
        ]);

        return ArrayType::mapByCallback($vehicles, function(KeyValuePair $pair) {
            $value = $pair->getValue();
            /* @var $value Vehicle */
            return new KeyValuePair($value->getId(), $value->getLabel());
        });

    }

    public function save(Vehicle $vehicle): void
    {
        $this->em->persist($vehicle)->flush();
    }

    /**
     * Removes vehicle with specified ID
     * @param $vehicleId
     * @return bool
     */
    public function remove($vehicleId): bool
    {
        try {
            $this->em->remove($this->get($vehicleId))->flush();
            return TRUE;
        } catch (VehicleNotFoundException $e) {
            return FALSE;
        }
    }

}
