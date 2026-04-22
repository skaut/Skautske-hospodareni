<?php

declare(strict_types=1);

namespace App\Model\Invoice\Repository;

use App\Model\Common\UnitId;
use App\Model\Infrastructure\Repository\AbstractRepository;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\Enum\InvoiceSequenceState;
use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\UnitNotFound;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

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
     * @param  int[]                $unitIds
     * @return array<string, mixed>
     */
    public function getGridByUnits(array $unitIds): array
    {
        if ($unitIds === []) {
            return [];
        }

        /** @var InvoiceSequence[] $invoiceSequence */
        $invoiceSequence = $this->createQueryBuilder('entity')
            ->where('entity.unit IN (:unitIds)')
            ->setParameter('unitIds', $unitIds)
            ->orderBy('entity.year', 'DESC')
            ->addOrderBy('entity.sequenceId', 'ASC')
            ->getQuery()
            ->getResult();
        $data = [];
        foreach ($invoiceSequence as $key => $value) {
            $data[$key] = $value->toArray();
            $data[$key]['invoiceCount'] = $value->getInvoices()->count();
            if ($value->getUnit() !== 0) {
                try {
                    $data[$key]['unitDisplayName'] = $this->unitRepository->find($value->getUnit())->getDisplayName();
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
     * @param  int[]                $unitIds
     * @return array<string, mixed>
     */
    public function getOpenGridByUnitsForYear(array $unitIds, int $year, ?int $limit = null): array
    {
        if ($unitIds === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('entity')
            ->where('entity.unit IN (:unitIds)')
            ->andWhere('entity.year = :year')
            ->andWhere('entity.state = :state')
            ->setParameter('unitIds', $unitIds)
            ->setParameter('year', $year)
            ->setParameter('state', InvoiceSequenceState::OPEN)
            ->orderBy('entity.sequenceId', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        /** @var InvoiceSequence[] $invoiceSequence */
        $invoiceSequence = $qb->getQuery()->getResult();
        $data = [];
        foreach ($invoiceSequence as $key => $value) {
            $data[$key] = $value->toArray();
            $data[$key]['invoiceCount'] = $value->getInvoices()->count();
            if ($value->getUnit() !== 0) {
                try {
                    $data[$key]['unitDisplayName'] = $this->unitRepository->find($value->getUnit())->getDisplayName();
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
     * @param int[] $unitIds
     */
    public function findAccessibleByUnits(int $id, array $unitIds): ?InvoiceSequence
    {
        return $this->createQueryBuilder('entity')
            ->where('entity.id = :id')
            ->andWhere('entity.unit IN (:unitIds)')
            ->setParameter('id', $id)
            ->setParameter('unitIds', $unitIds)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int[] $unitIds
     *
     * @return list<InvoiceSequence>
     */
    public function findOpenAccessibleByUnits(array $unitIds): array
    {
        if ($unitIds === []) {
            return [];
        }

        /** @var list<InvoiceSequence> $sequences */
        $sequences = $this->createQueryBuilder('entity')
            ->where('entity.unit IN (:unitIds)')
            ->andWhere('entity.state = :state')
            ->setParameter('unitIds', $unitIds)
            ->setParameter('state', InvoiceSequenceState::OPEN)
            ->orderBy('entity.year', 'DESC')
            ->addOrderBy('entity.sequenceId', 'ASC')
            ->getQuery()
            ->getResult();

        return $sequences;
    }

    public function save(InvoiceSequence $sequence): void
    {
        $this->entityManager->persist($sequence);
        $this->entityManager->flush();
    }

    /** @return list<InvoiceSequence> */
    public function findByBankAccount(int $bankAccountId): array
    {
        /** @var list<InvoiceSequence> $sequences */
        $sequences = $this->createQueryBuilder('entity')
            ->where('IDENTITY(entity.bankAccount) = :bankAccountId')
            ->setParameter('bankAccountId', $bankAccountId)
            ->orderBy('entity.year', 'DESC')
            ->addOrderBy('entity.sequenceId', 'ASC')
            ->getQuery()
            ->getResult();

        return $sequences;
    }

    /** @return list<InvoiceSequence> */
    public function findAutomaticPairingEnabled(): array
    {
        /** @var list<InvoiceSequence> $sequences */
        $sequences = $this->createQueryBuilder('entity')
            ->andWhere('entity.automaticPairingEnabled = :enabled')
            ->andWhere('entity.bankAccount IS NOT NULL')
            ->andWhere('entity.state = :state')
            ->setParameter('enabled', true)
            ->setParameter('state', InvoiceSequenceState::OPEN)
            ->orderBy('entity.unit', 'ASC')
            ->addOrderBy('entity.year', 'ASC')
            ->addOrderBy('entity.sequenceId', 'ASC')
            ->getQuery()
            ->getResult();

        return $sequences;
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
