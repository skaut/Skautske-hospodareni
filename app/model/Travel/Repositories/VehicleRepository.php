<?php

namespace Model\Travel\Repositories;

use Consistence\Type\ArrayType\ArrayType;
use Consistence\Type\ArrayType\KeyValuePair;
use Dibi\Connection;
use Kdyby\Doctrine\EntityManager;
use Model\BaseTable;
use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFoundException;

class VehicleRepository implements IVehicleRepository
{

    /** @var EntityManager */
    private $em;

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection, EntityManager $em)
    {
        $this->connection = $connection;
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

        $vehicle->setCommandsCount($this->countCommands([$id])[$id]);

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

        $commandCounts = $this->countCommands(array_keys($vehicles));

        array_map(function(Vehicle $vehicle) use($commandCounts) {
            $vehicle->setCommandsCount($commandCounts[$vehicle->getId()]);
        }, $vehicles);

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
            $this->em->remove($this->get($vehicleId));
            return TRUE;
        } catch (VehicleNotFoundException $e) {
            return FALSE;
        }
    }

    /**
     * @param array $vehicleIds
     * @return int[]
     */
    private function countCommands(array $vehicleIds)
    {
        $counts = $this->connection->select('vehicle_id, COUNT(id) as commandsCount')
            ->from(BaseTable::TABLE_TC_COMMANDS)
            ->where('vehicle_id IN (%i)', $vehicleIds)
            ->where('deleted = 0')
            ->groupBy('vehicle_id')
            ->execute()
            ->fetchPairs('vehicle_id', 'commandsCount');

        // Add vehicles without commands
        $counts += array_fill_keys(array_diff($vehicleIds, array_keys($counts)), 0);

        return $counts;
    }

}
