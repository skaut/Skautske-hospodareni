<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Doctrine\ORM\EntityManager;
use Model\Cashbook\Exception\UnitNotFound;
use Model\Cashbook\Repositories\IUnitRepository;
use Model\Cashbook\Unit;
use Model\Common\UnitId;

final class UnitRepository implements IUnitRepository
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function find(UnitId $id) : Unit
    {
        $unit = $this->entityManager->find(Unit::class, $id);

        if ($unit === null) {
            throw UnitNotFound::withId($id);
        }

        return $unit;
    }

    public function save(Unit $unit) : void
    {
        $this->entityManager->persist($unit);
        $this->entityManager->flush();
    }
}
