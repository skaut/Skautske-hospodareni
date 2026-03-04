<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Entity\InvoiceSequence;
use Model\Common\UnitId;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\UnitNotFound;

class InvoiceSequenceRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $entityManager, protected IUnitRepository $unitRepository)
    {
        parent::__construct($entityManager);
    }

    public function getEntityClass(): string
    {
        return InvoiceSequence::class;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGrid(): array
    {
        /** @var InvoiceSequence[] $invoiceSequence */
        $invoiceSequence = $this->findAll();
        $data = [];
        foreach ($invoiceSequence as $key => $value) {
            $data[$key] = $value->toArray();
            if ($value->getUnit() !== 0) {
                try {
                    $data[$key]['unitDisplayName'] = $this->unitRepository->find($value['unit'])->getDisplayName();
                } catch (UnitNotFound) {
                    $data[$key]['unitDisplayName'] = '';
                }
            } else {
                $data[$key]['unitDisplayName'] = '';
            }
        }

        return $data;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getNextSequenceId(UnitId $unitId, int $year): int
    {
        try {
            $sequenceId = $this->createQueryBuilder('entity')
                ->select('MAX(entity.sequenceId) as id')
                ->where('entity.unit = :unit')
                ->andWhere('entity.year = :year')
                ->setParameter('unit', $unitId->toInt())
                ->setParameter('year', $year)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException) {
            return 1;
        }

        return $sequenceId === null ? 1 : $sequenceId + 1;
    }
}
